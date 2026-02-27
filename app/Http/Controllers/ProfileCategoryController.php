<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\ProfileCategory;
use App\Models\ProfileSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileCategoryController extends Controller
{
    // Admin: list all profile categories with sub-categories
    public function listProfileCategories(Request $request)
    {
        $categories = ProfileCategory::with('subCategories')
            ->orderBy('account_type')
            ->orderBy('sort_order')
            ->get();

        return view('profileCategories', ['categories' => $categories]);
    }

    // Admin: add profile category
    public function addProfileCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'account_type' => 'required|integer|in:1,2,3,4',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $category = new ProfileCategory();
        $category->name = $request->name;
        $category->account_type = $request->account_type;
        $category->requires_approval = $request->requires_approval ?? false;
        $category->sort_order = $request->sort_order ?? 0;
        $category->save();

        return GlobalFunction::sendSimpleResponse(true, 'profile category added successfully');
    }

    // Admin: update profile category
    public function updateProfileCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:profile_categories,id',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $category = ProfileCategory::find($request->id);
        if ($request->has('name')) $category->name = $request->name;
        if ($request->has('is_active')) $category->is_active = $request->is_active;
        if ($request->has('requires_approval')) $category->requires_approval = $request->requires_approval;
        if ($request->has('sort_order')) $category->sort_order = $request->sort_order;
        $category->save();

        return GlobalFunction::sendSimpleResponse(true, 'profile category updated successfully');
    }

    // Admin: delete profile category
    public function deleteProfileCategory(Request $request)
    {
        $category = ProfileCategory::find($request->id);
        if (!$category) {
            return GlobalFunction::sendSimpleResponse(false, 'profile category not found');
        }
        $category->delete();

        return GlobalFunction::sendSimpleResponse(true, 'profile category deleted successfully');
    }

    // Admin: add sub-category
    public function addProfileSubCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:profile_categories,id',
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $subCategory = new ProfileSubCategory();
        $subCategory->category_id = $request->category_id;
        $subCategory->name = $request->name;
        $subCategory->sort_order = $request->sort_order ?? 0;
        $subCategory->save();

        return GlobalFunction::sendSimpleResponse(true, 'profile sub category added successfully');
    }

    // Admin: update sub-category
    public function updateProfileSubCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:profile_sub_categories,id',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $subCategory = ProfileSubCategory::find($request->id);
        if ($request->has('name')) $subCategory->name = $request->name;
        if ($request->has('is_active')) $subCategory->is_active = $request->is_active;
        if ($request->has('sort_order')) $subCategory->sort_order = $request->sort_order;
        $subCategory->save();

        return GlobalFunction::sendSimpleResponse(true, 'profile sub category updated successfully');
    }

    // Admin: delete sub-category
    public function deleteProfileSubCategory(Request $request)
    {
        $subCategory = ProfileSubCategory::find($request->id);
        if (!$subCategory) {
            return GlobalFunction::sendSimpleResponse(false, 'profile sub category not found');
        }
        $subCategory->delete();

        return GlobalFunction::sendSimpleResponse(true, 'profile sub category deleted successfully');
    }
}
