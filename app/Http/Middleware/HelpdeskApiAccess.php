<?php
namespace App\Http\Middleware;
use App\Models\HelpdeskApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Closure;
class HelpdeskApiAccess
{
    public function handle(Request $request,Closure $next,string $scope)
    {
        $token=$request->bearerToken();
        $key=$token ? HelpdeskApiKey::where('key_hash',hash('sha256',$token))->with('user')->first() : null;
        if (!$key || !$key->is_active || !$key->user?->is_active || $key->user->isUser() || $key->expires_at?->isPast()) return response()->json(['message'=>'Invalid or expired API key.'],401);
        if (!in_array($scope,$key->scopes,true) || ($key->allowed_ip && $key->allowed_ip!==$request->ip())) return response()->json(['message'=>'API key access denied.'],403);
        Auth::setUser($key->user); $request->setUserResolver(fn()=>$key->user);
        $key->update(['last_used_at'=>now()]); return $next($request);
    }
}
