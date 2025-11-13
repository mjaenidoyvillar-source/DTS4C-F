
public function handle($request, Closure $next, ...$guards)
{
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return $next($request);
}
