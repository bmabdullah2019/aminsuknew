<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\BannerCategory;
use App\Models\GeneralSetting;
use File;
use Illuminate\Http\Request;
use Toastr;

class GeneralSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:setting-list|setting-create|setting-edit|setting-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:setting-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:setting-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = GeneralSetting::orderBy('id', 'DESC')->get();

        return view('backEnd.settings.index', compact('show_data'));
    }

    public function create()
    {
        $bannerCategories = BannerCategory::query()
            ->where('status', 1)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return view('backEnd.settings.create', compact('bannerCategories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'facebook_verification' => 'required',
            'google_verification' => 'required',
            'meta_keyword' => 'required',
            'meta_description' => 'required',
            'white_logo' => 'required',
            'og_baner' => 'required',
            'favicon' => 'required',
            'status' => 'required',
            'sidebar_banner_category_id' => 'nullable|integer|exists:banner_categories,id',
        ]);

        // white logo
        $image = $request->file('white_logo');
        $name = time().'-'.$image->getClientOriginalName();
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        $uploadpath = 'public/uploads/settings/';
        $imageUrl = $uploadpath.$name;
        ImageHelper::processAndSaveWebp($image->getRealPath(), $imageUrl, 90);

        // dark logo
        $image2 = $request->file('dark_logo');
        $name2 = time().'-'.$image2->getClientOriginalName();
        $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
        $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
        $uploadpath2 = 'public/uploads/settings/';
        $image2Url = $uploadpath2.$name2;
        ImageHelper::processAndSaveWebp($image2->getRealPath(), $image2Url, 90);

        // OG Baner
        $image4 = $request->file('og_baner');
        $name4 = time().'-'.$image4->getClientOriginalName();
        $name4 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name4);
        $name4 = strtolower(preg_replace('/\s+/', '-', $name4));
        $uploadpath4 = 'public/uploads/settings/';
        $image4Url = $uploadpath4.$name4;
        ImageHelper::processAndSaveWebp($image4->getRealPath(), $image4Url, 90);

        // favicon
        $image3 = $request->file('favicon');
        $name3 = time().'-'.$image3->getClientOriginalName();
        $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
        $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
        $uploadpath3 = 'public/uploads/settings/';
        $image3Url = $uploadpath3.$name3;
        ImageHelper::resizeAndSaveWebp($image3->getRealPath(), $image3Url, 32, 32, 90);

        $input = $request->all();
        $input['white_logo'] = $imageUrl;
        $input['dark_logo'] = $image2Url;
        $input['favicon'] = $image3Url;
        $input['og_baner'] = $image4Url;
        $input['sidebar_banner_category_id'] = $request->filled('sidebar_banner_category_id')
            ? (int) $request->sidebar_banner_category_id
            : null;
        GeneralSetting::create($input);

        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.settings.index');
    }

    public function edit($id)
    {
        $edit_data = GeneralSetting::findOrFail($id);
        $bannerCategories = BannerCategory::query()
            ->where('status', 1)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return view('backEnd.settings.edit', compact('edit_data', 'bannerCategories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'sidebar_banner_category_id' => 'nullable|integer|exists:banner_categories,id',
        ]);
        $update_data = GeneralSetting::findOrFail($request->id);
        $input = $request->all();
        // new white logo
        $image = $request->file('white_logo');
        if ($image) {
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/settings/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::processAndSaveWebp($image->getRealPath(), $imageUrl, 90);
            $input['white_logo'] = $imageUrl;
        } else {
            $input['white_logo'] = $update_data->white_logo;
        }
        // new dark logo
        $image2 = $request->file('dark_logo');
        if ($image2) {
            $name2 = time().'-'.$image2->getClientOriginalName();
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
            $uploadpath2 = 'public/uploads/settings/';
            $image2Url = $uploadpath2.$name2;
            ImageHelper::processAndSaveWebp($image2->getRealPath(), $image2Url, 90);
            $input['dark_logo'] = $image2Url;
        } else {
            $input['dark_logo'] = $update_data->dark_logo;
        }

        // new OG image
        $image4 = $request->file('og_baner');
        if ($image4) {
            $name4 = time().'-'.$image4->getClientOriginalName();
            $name4 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name4);
            $name4 = strtolower(preg_replace('/\s+/', '-', $name4));
            $uploadpath4 = 'public/uploads/settings/';
            $image4Url = $uploadpath4.$name4;
            ImageHelper::processAndSaveWebp($image4->getRealPath(), $image4Url, 90);
            $input['og_baner'] = $image4Url;
        } else {
            $input['og_baner'] = $update_data->og_baner;
        }

        // new favicon image
        $image3 = $request->file('favicon');
        if ($image3) {
            $name3 = time().'-'.$image3->getClientOriginalName();
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
            $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
            $uploadpath3 = 'public/uploads/settings/';
            $image3Url = $uploadpath3.$name3;
            ImageHelper::resizeAndSaveWebp($image3->getRealPath(), $image3Url, 32, 32, 90);
            $input['favicon'] = $image3Url;
        } else {
            $input['favicon'] = $update_data->favicon;
        }
        $input['sidebar_banner_category_id'] = $request->filled('sidebar_banner_category_id')
            ? (int) $request->sidebar_banner_category_id
            : null;
        $input['status'] = 1;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.settings.index');
    }

    public function inactive(Request $request)
    {
        $inactive = GeneralSetting::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = GeneralSetting::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = GeneralSetting::findOrFail($request->hidden_id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
