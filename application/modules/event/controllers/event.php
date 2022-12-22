<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Event extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Event_model', 'model', TRUE);
        $this->load->model('Event_detail_model', 'dmodel', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->product = new Product_lib();
        $this->payment = new Payment_lib();
        $this->member = new Member_lib();
        
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $api, $acl, $member;
    private $product,$payment;
    
    function index()
    {
        if ($this->api->otentikasi() == TRUE){
        $datax = (array)json_decode(file_get_contents('php://input')); 

        $decoded = $this->api->get_decoded();
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        if (isset($datax['publish'])){ $publish = $datax['publish']; }
        
        $result = $this->dmodel->get_last($decoded->userid,$this->limitx, $this->offsetx)->result();
        $this->count = $this->dmodel->get_last($decoded->userid,$this->limitx, $this->offsetx,1);

	foreach($result as $res)
	{   
            $event = $this->model->get_by_id($res->event_id)->row();
            $this->resx[] = array ("id"=>$res->id, "event_id"=>$res->event_id, "event_name" => $event->name,
                                   "tenant_id"=>$res->tenant_id,
                                   "member_name"=> $this->member->get($res->tenant_id).' - '.$this->member->get($res->tenant_id,'member_no'),
                                   "status"=>$res->status, "joined"=> tglincomplete($res->joined),
                                   "paid_method" => $this->payment->get_name($res->paid_method), "transid" => $res->transid,
                                   "paid_date"=> tglincomplete($res->paid_date), "amount" => $res->amount,
                                  );
	}
        
        $data['record'] = $this->count; 
        $data['result'] = $this->resx; 
        $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('content');
    } 

    private function cek_relation($id)
    {
        $product = $this->product->cek_relation($id, $this->title);
        if ($product == TRUE) { return TRUE; } else { return FALSE; }
    }

    function register()
    {
        if ($this->api->otentikasi() == TRUE){

	$datax = (array)json_decode(file_get_contents('php://input')); 

        $decoded = $this->api->get_decoded();
        if (!isset($datax['event_id'])){ $this->reject('Event-ID required',400); }
        elseif ($this->model->cek_trans('id',$datax['event_id']) == FALSE){ $this->reject('Invalid Event'); }
        elseif ($this->model->valid_active_event($datax['event_id']) == FALSE){ $this->reject('Event Not Active'); }
        else{
          if ($this->valid_register($decoded->userid, $datax['event_id']) == TRUE){
              
            $event = array('tenant_id' => $decoded->userid,'event_id' => $datax['event_id'],
                           'transid' => $this->dmodel->counter_model().$decoded->userid.mt_rand(1000,9999),
                           'joined' => date('Y-m-d H:i:s'),'created' => date('Y-m-d H:i:s'));
            
            if ($this->dmodel->add($event) != true){ $this->reject(); }else{ $this->dmodel->log('create'); $this->output = $this->dmodel->get_latest(); }
          }
        }

        }else{ $this->reject_token(); }
        $this->response('c');
    }
    
    function payment_confirmation()
    {
        if ($this->api->otentikasi() == TRUE){
  
        $this->form_validation->set_rules('transid', 'Name', 'required|callback_valid_trans');
        $this->form_validation->set_rules('pdate', 'Paid Date', 'required');
        $this->form_validation->set_rules('cpayment', 'Payment Type', 'required|callback_valid_payment'); // valid payment type
        $this->form_validation->set_rules('tamount', 'Amount', 'required|numeric');
        $this->form_validation->set_rules('tsourceno', 'Source-Acc No', 'required');
        $this->form_validation->set_rules('tsourcename', 'Source-Acc Name', 'required');
        $this->form_validation->set_rules('tsourcebank', 'Source-Acc Bank', 'required');
        $decoded = $this->api->get_decoded();
        
        if ($this->form_validation->run($this) == TRUE)
        {
            $config['upload_path'] = $this->properti['url_upload'].'devent/';
            $config['file_name'] = split_space($this->input->post('transid'));
            $config['allowed_types'] = 'jpg|gif|png|jpeg';
            $config['overwrite'] = true;
            $config['max_size']	= '50000';
            $config['max_width']  = '30000';
            $config['max_height']  = '30000';
            $config['remove_spaces'] = TRUE;

            $this->load->library('upload', $config);
            $source = $this->input->post('tsourceno').' - '.$this->input->post('tsourcename').' - '.$this->input->post('tsourcebank');

            if ( !$this->upload->do_upload("userfile")) // if upload failure
            {   
                $info['file_name'] = null;
                $data['error'] = $this->upload->display_errors();
                $member = array('paid_date' => $this->input->post('pdate'),'amount' => $this->input->post('tamount'), 
                                'paid_method' => $this->input->post('cpayment'), 'source_acc' => $source, 'status'=>1,'image' => null,);
            }
            else
            {
                $info = $this->upload->data();
                $member = array('paid_date' => $this->input->post('pdate'),'amount' => $this->input->post('tamount'), 
                                'paid_method' => $this->input->post('cpayment'), 'source_acc' => $source, 'status'=>1,
                                'image' => $info['file_name']);
            }

//            $this->balance->create($this->Member_model->counter(1), $this->period->month, $this->period->year);
            
            if ($this->dmodel->update_bytrans($this->input->post('transid'), $member) != true){ $this->reject($this->upload->display_errors());
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400); }

        }else{ $this->reject_token(); }
        $this->response('c');
    }

    public function valid_register($member,$event)
    {
        if ($this->dmodel->valid('tenant_id',$member) == FALSE && $this->dmodel->valid('event_id',$event) == FALSE)
        {
            $this->reject("This member $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_trans($val){
        if ($this->dmodel->cek_trans('transid',$val) == TRUE){
           if ($this->dmodel->valid_trans($val) == TRUE){ return TRUE; }else{
              $this->form_validation->set_message('valid_trans','Transaction has been confirmed..!'); return FALSE; 
           }
        }else{
           $this->form_validation->set_message('valid_trans','Invalid Trans-Id..!');
           return FALSE; 
        }
    }
    
    function valid_payment($val){
        
        if ($this->payment->cek_trans('id', $val) == FALSE)
        {
            $this->form_validation->set_message('valid_payment','Invalid Payment Type..!');
            return FALSE;
        }
        else{ return TRUE; }
    }


}

?>