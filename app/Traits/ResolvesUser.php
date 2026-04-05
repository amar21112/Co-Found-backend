<?php
namespace App\Traits;
trait ResolvesUser
{
    public function resolveUser($request)
    {
        if (auth()->check()) {
            return auth()->user();
        }

         return \App\Models\User::find($request->user_id ?? 1);
    }
}
/*
 *
 * use App\Traits\ResolvesUser;

    class nameController extends Controller
    {
        use ResolvesUser;

        public function name(Request $request)
        {
            $user = $this->resolveUser($request);
            your logic
        }
    }
       in api request send user_id field , after finish authentication module delete it .
        and i will delete the check in function above will just return auth()->user;
        and can use middleware and brear token latter
*/
