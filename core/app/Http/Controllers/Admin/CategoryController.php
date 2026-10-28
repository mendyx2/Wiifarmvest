<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /** Shop → Manage Category (Pages/Admin/Shop/Categories.tsx). */
    public function index()
    {
        $pageTitle  = 'Categories';
        $categories = Category::searchable(['name'])->withCount('products')->orderBy('name')->paginate(getPaginate())->withQueryString()
            ->through(fn (Category $category) => [
                'id'       => $category->id,
                'name'     => (string) $category->name,
                'enabled'  => (int) $category->status === Status::ENABLE,
                'products' => (int) $category->products_count,
            ]);

        return Inertia::render('Admin/Shop/Categories', [
            'categories' => $categories,
            'search'     => (string) request()->query('search', ''),
            'urls'       => [
                'store'  => route('admin.category.store'),
                'status' => route('admin.category.status', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request, $id = 0)
    {
        $request->validate([
            // Two categories with the same name made the shop filter ambiguous.
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($id)],
        ]);

        if (!$id) {
            $notification = 'Category added successfully';
            $category     = new Category();
        } else {
            $notification = 'Category updated successfully';
            $category     = Category::findOrFail($id);
        }

        $category->name = trim($request->name);
        $category->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Category::changeStatus($id);
    }
}
