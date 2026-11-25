<?php
namespace App\Services;
use App\Models\Ticket;
use App\Http\Controllers\DashboardSettingsController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class TicketNumberSeries {
 public function next(): string {
  return DB::transaction(function(){
   $row=DB::table('ticket_number_sequences')->where('id',1)->lockForUpdate()->first();
   $s=DashboardSettingsController::settings();
   $parts=array_filter([$s['number_prefix'], $s['number_date']==='none'?'':now()->format($s['number_date'])],fn($v)=>$v!=='');
   $base=implode('-',$parts); $base=$base===''?'':$base.'-';
   if($s['number_mode']==='random') { do{$number=$base.strtoupper(Str::random(10));}while(Ticket::where('ticket_number',$number)->exists()); return $number; }
   $next=(int)$row->next_number;
   do {
    if($next>999999999999) throw \Illuminate\Validation\ValidationException::withMessages(['ticket'=>'The ticket number series is exhausted. Ask an administrator to update Settings.']);
    $number=$base.str_pad((string)$next,(int)$s['number_padding'],'0',STR_PAD_LEFT);$next++;
   } while(Ticket::where('ticket_number',$number)->exists());
   DB::table('ticket_number_sequences')->where('id',1)->update(['next_number'=>$next]);
   return $number;
  });
 }
}
