<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Resources\PermissionResource; // Create this

class PermissionController extends Controller
{
     public function __construct()
    {
        // Protect all methods in this controller
        $this->middleware('can:permission_view_any')->only('index');
    }

    public function index(Request $request)
    {
        // Permissions are usually not created/edited via API by users other than super-admin seeding them
        // This endpoint is for listing them to assign to roles
        $query = Permission::orderBy('name');
         if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }
        return PermissionResource::collection($query->get());
    }
}