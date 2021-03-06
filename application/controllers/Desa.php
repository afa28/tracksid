<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Desa extends CI_Controller {

  function __construct(){
    parent::__construct();
    $this->load->helper('url');
    $this->load->model('desa_model');
    if (!admin_logged_in()) redirect('login');
  }

	public function index()
	{
	}

	public function hapus($id){
		$this->load->model('desa_model');
		$this->desa_model->hapus($id);
		redirect('/');
	}

	/*
		Ajax url query data:
		q -- kata pencarian
		page -- nomor paginasi
	*/
	public function list_desa_ajax()
	{
		$cari = $this->input->get('q');
		$page = $this->input->get('page');
		$desa = $this->desa_model->list_desa_ajax($cari, $page);
		echo json_encode($desa);
	}

	public function lihat($desa_id)
	{
    $header = new stdClass();
    $header->title = "Desa xxx";
    $data['desa'] = $this->desa_model->get_desa($desa_id);

    $this->load->view('dashboard/header', $header);
    $this->load->view('dashboard/nav');
    $this->load->view('desa/show', $data);
    $this->load->view('footer');
	}

}
