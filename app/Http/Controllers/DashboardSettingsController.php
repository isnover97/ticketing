<?php
namespace App\Http\Controllers;
use App\Models\HelpdeskRecord;
use Illuminate\Http\Request;
class DashboardSettingsController extends Controller {
 public static function settings(): array { return array_replace(['name'=>'PREI HelpDesk','support_email'=>'support@pascalresources.com.ph','display_timezone'=>'UTC','page_size'=>25,'show_stats'=>1,'default_priority'=>'medium','number_mode'=>'random','number_prefix'=>'TIC','number_date'=>'Ymd','number_padding'=>5],HelpdeskRecord::ofKind('dashboard_settings')->first()?->settings ?? []); }
 public function index(){ return view('helpdesk.settings',['settings'=>self::settings(),'nextNumber'=>\Illuminate\Support\Facades\DB::table('ticket_number_sequences')->where('id',1)->value('next_number')]); }
 public function save(Request $request){
 $data=$request->validate(['number_mode'=>'sometimes|required|in:random,sequential','number_prefix'=>['sometimes','nullable','string','max:20','regex:/^[A-Za-z0-9_-]*$/'],'number_date'=>'sometimes|required|in:none,Ymd,Ym,Y','number_padding'=>'sometimes|required|integer|between:1,12','next_number'=>'nullable|integer|min:1|max:999999999999','name'=>'required|string|max:80','support_email'=>'required|email|max:255','display_timezone'=>'required|timezone','page_size'=>'required|integer|in:10,25,50,100','show_stats'=>'required|boolean','default_priority'=>'required|in:low,medium,high,urgent']);
 \Illuminate\Support\Facades\DB::transaction(function() use ($data) {
  \Illuminate\Support\Facades\DB::table('ticket_number_sequences')->where('id',1)->lockForUpdate()->first();
  if(isset($data['next_number'])) \Illuminate\Support\Facades\DB::table('ticket_number_sequences')->where('id',1)->update(['next_number'=>$data['next_number']]);
  unset($data['next_number']);
  if(array_key_exists('number_prefix',$data)) $data['number_prefix']=$data['number_prefix'] ?? '';
  HelpdeskRecord::updateOrCreate(['kind'=>'dashboard_settings'],['name'=>'Dashboard settings','settings'=>array_replace(self::settings(),$data),'is_active'=>true]);
 });
 app(\App\Services\TicketWorkflow::class)->event(null,$request->user(),'settings','Dashboard settings updated.');
 return redirect()->route('helpdesk.settings')->with('status','Settings saved.'); }
}



