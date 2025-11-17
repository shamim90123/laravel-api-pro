<?php

namespace App\Http\Controllers\Api\Config;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct()
    {
        // Spatie permission gates per action
        $this->middleware('permission:menu.view')->only(['index', 'show']);
        $this->middleware('permission:menu.create')->only(['store']);
        $this->middleware('permission:menu.update')->only(['update']);
        $this->middleware('permission:menu.delete')->only(['destroy']);
        $this->middleware('permission:menu.toggle-status')->only(['toggleStatus']);
    }

    /**
     * Get all menus (sorted + nested optional)
     */
    public function index()
    {
        $menus = Menu::orderBy('sort_order')->orderBy('name')->get();
        return response()->json($menus);
    }

    /**
     * Store a new menu
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'icon'             => 'nullable|string|max:100',
            'route'            => 'nullable|string|max:255',
            'parent_id'        => 'nullable|integer',
            'sort_order'       => 'integer',
            'is_active'        => 'boolean',
            'permission_name'  => 'nullable|string|max:255',
        ]);

        $menu = Menu::create([
            'name'            => $request->name,
            'icon'            => $request->icon,
            'route'           => $request->route,
            'parent_id'       => $request->parent_id,
            'sort_order'      => $request->sort_order ?? 0,
            'is_active'       => $request->is_active ?? 1,
            'permission_name' => $request->permission_name,
        ]);

        return response()->json($menu, 201);
    }

    /**
     * Show a single menu
     */
    public function show($id)
    {
        $menu = Menu::findOrFail($id);
        return response()->json($menu);
    }

    /**
     * Update menu
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $request->validate([
            'name'             => 'required|string|max:255',
            'icon'             => 'nullable|string|max:100',
            'route'            => 'nullable|string|max:255',
            'parent_id'        => 'nullable|integer',
            'sort_order'       => 'integer',
            'is_active'        => 'boolean',
            'permission_name'  => 'nullable|string|max:255',
        ]);

        $menu->update($request->all());

        return response()->json($menu);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $menu = Menu::findOrFail($id);

        $menu->is_active = !$menu->is_active;
        $menu->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'is_active' => $menu->is_active
        ]);
    }


    /**
     * Delete menu
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return response()->json(null, 204);
    }

   public function parents()
    {
        $parents = Menu::whereNull('parent_id')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json($parents);
    }



}
