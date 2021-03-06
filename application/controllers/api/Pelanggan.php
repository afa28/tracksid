<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Pelanggan extends REST_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model(['pelanggan_model_api', 'referensi_model']);
    $this->pelanggan = $this->pelanggan_model_api;
  }

  public function admin_get()
  {
    $token = $this->input->get('token');
    $admin_id = $this->pelanggan->get_admin_id_from_token($token);
    $admin_token = $this->pelanggan->get_admin_token_from_id($admin_id);
    $invalidLogin = ['status' => '401 Unauthorized'];
    if ($token === $admin_token) {
      $decodedToken = AUTHORIZATION::validateTimestamp($token);
      if ($decodedToken != false) {
        $this->set_response($decodedToken, REST_Controller::HTTP_OK);
        $response = $this->pelanggan->api_get_all_customer();
        $this->response($response);
        return;
      }
    }
    $this->set_response($invalidLogin, REST_Controller::HTTP_UNAUTHORIZED);
  }

  public function admincs_get()
  {
    $token = $this->input->get('token');
    $customer_id = $this->input->get('id');
    $admin_id = $this->pelanggan->get_admin_id_from_token($token);
    $admin_token = $this->pelanggan->get_admin_token_from_id($admin_id);
    $invalidLogin = ['status' => '401 Unauthorized'];
    if ($token === $admin_token) {
      $decodedToken = AUTHORIZATION::validateTimestamp($token);
      if ($decodedToken != false) {
        $this->set_response($decodedToken, REST_Controller::HTTP_OK);
        $response = $this->pelanggan->api_get_customer($customer_id);
        $this->response($response);
        return;
      }
    }
    $this->set_response($invalidLogin, REST_Controller::HTTP_UNAUTHORIZED);
  }

  public function customer_get()
  {
    $token = $this->input->get('token');
    $customer_id = $this->pelanggan->get_customer_id_from_token($token);
    $customer_token = $this->pelanggan->get_customer_token_from_id($customer_id);
    $invalidLogin = ['status' => '401 Unauthorized'];
    if ($token === $customer_token) {
      $decodedToken = AUTHORIZATION::validateTimestamp($token);
      if ($decodedToken != false) {
        $this->set_response($decodedToken, REST_Controller::HTTP_OK);
        $response = $this->pelanggan->api_get_customer($customer_id);
        $this->response($response);
        return;
      }
    }
    $this->set_response($invalidLogin, REST_Controller::HTTP_UNAUTHORIZED);
  }

}
