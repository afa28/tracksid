<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Pelanggan extends Admin_Controller{

	function __construct()
	{
		parent::__construct();

		$this->load->model(['pelanggan_model', 'referensi_model', 'pelanggan_model_api',]);
		$this->load->helper('url');
		$this->load->library('pagination');
		//if ( ! admin_logged_in()) redirect('login'); enable development
	}

	/*
	 * Listing of pelanggan
	 */

	public function clear()
	{
		$this->session->filter = null;
		redirect('pelanggan/index');
	}

	public function index()
	{
		$params['limit'] = 20; // jumlah records per halaman
		$params['offset'] = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
		$filter = $this->input->post('filter');
		if (isset($filter))
		{
			$this->session->filter = $filter;
		}
		elseif (isset($this->session->filter))
		{
			$filter = $this->session->filter;
		}

		$data['jenis_pelanggan'] =  $this->referensi_model->list_ref(JENIS_PELANGGAN);
		$data['status_langganan'] = $this->referensi_model->list_ref(STATUS_LANGGANAN);
		$data['filter_langganan'] = $this->referensi_model->list_ref(FILTER_LANGGANAN);
		$data['pelaksana'] = $this->referensi_model->list_ref(PELAKSANA);
		$data['selected_filter'] = $filter;

		$this->load->view('dashboard/header');
		$this->load->view('dashboard/nav');
		$this->load->view('pelanggan/index', $data);
		$this->load->view('dashboard/footer');
	}

	// Ambil view pecahan untuk kolom aksi tabel pelanggan
	private function aksi($data)
	{
		$str = $this->load->view('pelanggan/pajax.index.php', ['data' => $data], TRUE);
		return $str;
	}

	public function ajax_list_pelanggan()
	{
		$list = $this->pelanggan_model->get_filtered_pelanggan();

		$data = array();
		$no = $_POST['start'];
		foreach ($list as $pelanggan)
		{
			$no++;
			$row = array();
			$row[] = $no;
			$row[] = $this->aksi($pelanggan);
			$row[] = $pelanggan['domain'];
			$row[] = $pelanggan['desa'];
			$row[] = $pelanggan['nama'];
			$row[] = $pelanggan['no_hp'];
			$row[] = ucwords($this->referensi_model->list_ref(JENIS_PELANGGAN)[$pelanggan['jenis_langganan']]);
			$row[] = tgl_out($pelanggan['tgl_akhir']);
			$row[] = ucwords($this->referensi_model->list_ref(STATUS_LANGGANAN)[$pelanggan['status_langganan']]);
			$row[] = $this->referensi_model->list_ref(PELAKSANA)[$pelanggan['pelaksana']];

			$data[] = $row;
		}

		$output = array
		(
			"draw" => $_POST['draw'],
			"recordsTotal" => $this->pelanggan_model->get_all_pelanggan_count(),
			"recordsFiltered" => $this->pelanggan_model->count_filtered(),
			"data" => $data,
		);

		//output to json format
		echo json_encode($output);
	}

	/*
	 * Tambah dan ubah data pelanggan.
	 * Untuk ubah desa perlu penanganan khusus
	 * TODO: perbaiki ubah desa menggunakan ajax langsung
	 */
	function form($id = null)
	{
		$data['pelanggan'] = null;
		$data['id_pelanggan'] = null;
		$data['error'] = null;
		$upload = null;

		if (empty($this->input->post('ubah_desa')) && $id)
		{
			$data['pelanggan'] = $this->pelanggan_model->get_pelanggan($id);
			if (empty($data['pelanggan']))
				show_error('Pelanggan itu tidak ditemukan.');
			else
				$data['desa'] = $this->db->where('id', $data['pelanggan']['id_desa'])->get('desa')->row_array();
		}
		else
		{
			$data['desa'] = ($id_desa = $this->input->post('id_desa')) ? $this->db->where('id', $id_desa)->get('desa')->row_array() : null;
		}

		$this->load->library('form_validation');

		$this->form_validation->set_rules('domain','Domain','required|valid_url');
		$this->form_validation->set_rules('id_desa','Desa','required|integer');
		$this->form_validation->set_rules('nama','Nama','required|alpha_numeric_spaces');
		$this->form_validation->set_rules('no_hp','No. HP','required|numeric');
		$this->form_validation->set_rules('email','Email','valid_email');
		$this->form_validation->set_rules('jenis_langganan','Jenis Langganan','required|integer');
		$this->form_validation->set_rules('tgl_mulai','Tgl Mulai','required');
		$this->form_validation->set_rules('tgl_akhir','Tgl Akhir','required');
		$this->form_validation->set_rules('status_langganan','Status Langganan','numeric');
		$this->form_validation->set_rules('pelaksana','Pelaksana','required|alpha_numeric_spaces');

		if (empty($this->input->post('ubah_desa')) && $this->form_validation->run())
		{
			$params = array(
				'domain' => $this->input->post('domain'),
				'id_desa' => $this->input->post('id_desa'),
				'nama' => $this->input->post('nama'),
				'no_hp' => $this->input->post('no_hp'),
				'email' => $this->input->post('email'),
				'jenis_langganan' => $this->input->post('jenis_langganan'),
				'tgl_mulai' => tgl_in($this->input->post('tgl_mulai')),
				'tgl_akhir' => tgl_in($this->input->post('tgl_akhir')),
				'status_langganan' => $this->input->post('status_langganan'),
				'pelaksana' => $this->input->post('pelaksana'),
				'token' => $this->input->post('token')
			);

			if ($this->input->post('hapus_bukti'))
			{
				hapus_bukti($this->input->post('bukti_lama'));
				$params['bukti'] = null;
			}
			if ( ! empty($_FILES['bukti']['name'])) $upload = $this->do_upload();
			if (empty($upload['error']))
			{
				if ( ! empty($_FILES['bukti']['name']))
				{
					$params['bukti'] = $upload['upload_data']['file_name'];
					if ($bukti_lama = $this->input->post('bukti_lama')) hapus_bukti($bukti_lama);
				}
				if ($id)
					$this->pelanggan_model->update_pelanggan($id, $params);
				else
				{
					$this->pelanggan_model->add_pelanggan($params);
				}
				redirect('pelanggan/index');
			}
		}
		$data['error'] = $upload['error'] ?: null;
		if ($this->input->post('ubah_desa')) $data['id_pelanggan'] = $id;
		$this->render_form($data);
	}

	private function do_upload()
	{
		$config['upload_path']          = './uploads/';
		$config['allowed_types']        = 'gif|jpg|png';
		$config['max_size']             = 1000;
		$config['max_width']            = 4096;
		$config['max_height']           = 2000;

		$this->load->library('upload', $config);
		if ( ! $this->upload->do_upload('bukti'))
		{
			$data['error'] = $this->upload->display_errors();
		}
    else
    {
      $data['upload_data'] = $this->upload->data();
    }
		return $data;
	}

	private function render_form($data)
	{
		$data['status_aktif'] = $this->referensi_model->list_ref(STATUS_AKTIF);
		$data['jenis_pelanggan'] = $this->referensi_model->list_ref(JENIS_PELANGGAN);
		$data['pelaksana'] = $this->referensi_model->list_ref(PELAKSANA);
		$data['status_langganan'] = $this->referensi_model->list_ref(STATUS_LANGGANAN);
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/nav');
		$this->load->view('pelanggan/form', $data);
		$this->load->view('dashboard/footer');
	}

	public function cek_kode($kode)
	{
		$id = $this->input->post('id');
		$ada = $this->Notif_model->cek_kode($kode, $id);
		if ($ada)
		{
			$this->form_validation->set_message('cek_kode', 'Kode pelanggan itu sudah ada');
			return FALSE;
		}

		return TRUE;
	}

	/*
	 * Deleting pelanggan
	 */
	public function remove($id)
	{
		$pelanggan = $this->pelanggan_model->get_pelanggan($id);

		// check if the pelanggan exists before trying to delete it
		if (isset($pelanggan['id']))
		{
			$this->pelanggan_model->delete_pelanggan($id);
			redirect('pelanggan/index');
		}
		else
			show_error('Pelanggan tersebut tidak ditemukan.');
	}

	public function lock($id = 0, $aktif = 0)
	{
		$this->Notif_model->lock($id, $aktif);
		redirect("pelanggan");
	}

	public function generate_token()
	{
		$id = $this->input->post('id');
		$data = $this->pelanggan_model->generate_token($id);
		echo json_encode($data);
	}

}
