<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CoinPackages;
use App\Models\Constants;
use App\Models\ColorFilter;
use App\Models\FaceSticker;
use App\Models\DummyLiveVideos;
use App\Models\Gifts;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Language;
use App\Models\MusicCategories;
use App\Models\OnboardingScreens;
use App\Models\PaymentGateway;
use App\Models\RedeemGateways;
use App\Models\ReportReasons;
use App\Models\UserLevels;
use App\Models\Users;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{

    public function androidDeepLinking(Request $request)
    {
        $request->validate([
            'sha_256' => 'required|array',
            'sha_256.*' => 'string', // each element must be a string
            'package_name' => 'required|string',
        ]);

        $filePath = public_path('assets/assetlinks.json');

        // Convert all values to uppercase
        $shaArray = array_map(function ($val) {
            return strtoupper(trim($val));
        }, $request->sha_256);

        // Build new JSON structure (overwrite everything)
        $data = [
            [
                "relation" => ["delegate_permission/common.handle_all_urls"],
                "target" => [
                    "namespace" => "android_app",
                    "package_name" => $request->package_name,
                    "sha256_cert_fingerprints" => $shaArray,
                ]
            ]
        ];

        // Save file (pretty JSON)
        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'status' => true,
            'message' => 'assetlinks.json file replaced successfully',
            'data' => $data,
        ]);
    }

    public function iOSDeepLinking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|string',
            'package_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $teamId = strtoupper(trim($request->team_id)); // Ensure uppercase
        $packageName = trim($request->package_name);

        // Build AppID
        $appId = $teamId . '.' . $packageName;

        // Construct AASA structure
        $aasaData = [
            "applinks" => [
                "apps" => [],
                "details" => [
                    [
                        "appIDs" => [$appId],
                        "components" => [
                            [
                                "/" => "*",
                                "?" => ["\$web_only" => "true"],
                                "exclude" => true,
                                "comment" => "Exclude web_only links"
                            ],
                            [
                                "/" => "*",
                                "?" => ["%24web_only" => "true"],
                                "exclude" => true,
                                "comment" => "Exclude encoded web_only links"
                            ],
                            [
                                "/" => "/e/*",
                                "exclude" => true,
                                "comment" => "Exclude /e/* paths"
                            ],
                            [
                                "/" => "*",
                                "comment" => "Allow all other paths"
                            ],
                            [
                                "/" => "/",
                                "comment" => "Allow root path"
                            ]
                        ]
                    ]
                ]
            ],
            "webcredentials" => [
                "apps" => [$appId]
            ]
        ];

        // Save to root public folder (no extension for iOS)
        $filePath = public_path('assets/apple-app-site-association');
        File::put($filePath, json_encode($aasaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'status' => true,
            'message' => 'iOS Deep Linking settings saved successfully.',
        ]);
    }

    function testingRoute(){
        $user = Users::find(17);
        GlobalFunction::deleteUserAccount($user);
    }

    function editUserLevel(Request $request){
        $item = UserLevels::find($request->id);
        $item->coins_collection = $request->coins_collection;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item edited successfully!');
    }
    function addUserLevel(Request $request){
        $item = UserLevels::where('level', $request->level)->first();
        if($item != null){
            return GlobalFunction::sendSimpleResponse(false,'User level exists already');
        }
        $item = new UserLevels();
        $item->level = $request->level;
        $item->coins_collection = $request->coins_collection;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item added successfully!');
    }
    function addWithdrawalGateway(Request $request){
        $item = new RedeemGateways();
        $item->title = $request->title;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item added successfully!');
    }
    function editColorFilter(Request $request){
        $item = ColorFilter::find($request->id);
        $item->title = $request->title;
        if($request->has('image')){
            $item->image = GlobalFunction::saveFileAndGivePath($request->image);
        }
        if($request->has('color_matrix')){
            $item->color_matrix = json_decode($request->color_matrix, true);
        }
        $item->brightness = $request->brightness ?? 0;
        $item->contrast = $request->contrast ?? 1.0;
        $item->saturation = $request->saturation ?? 1.0;
        $item->warmth = $request->warmth ?? 0;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item edited successfully!');
    }
    function addColorFilter(Request $request){
        $item = new ColorFilter();
        $item->title = $request->title;
        $item->image = GlobalFunction::saveFileAndGivePath($request->image);
        if($request->has('color_matrix')){
            $item->color_matrix = json_decode($request->color_matrix, true);
        }
        $item->brightness = $request->brightness ?? 0;
        $item->contrast = $request->contrast ?? 1.0;
        $item->saturation = $request->saturation ?? 1.0;
        $item->warmth = $request->warmth ?? 0;
        $item->position = ColorFilter::max('position') + 1;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item added successfully!');
    }
    function editFaceSticker(Request $request){
        $item = FaceSticker::find($request->id);
        $item->title = $request->title;
        if($request->has('thumbnail')){
            $item->thumbnail = GlobalFunction::saveFileAndGivePath($request->thumbnail);
        }
        if($request->has('sticker_image')){
            $item->sticker_image = GlobalFunction::saveFileAndGivePath($request->sticker_image);
        }
        $item->anchor_landmark = $request->anchor_landmark ?? 'nose';
        $item->scale = $request->scale ?? 1.0;
        $item->offset_x = $request->offset_x ?? 0;
        $item->offset_y = $request->offset_y ?? 0;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item edited successfully!');
    }
    function addFaceSticker(Request $request){
        $item = new FaceSticker();
        $item->title = $request->title;
        $item->thumbnail = GlobalFunction::saveFileAndGivePath($request->thumbnail);
        $item->sticker_image = GlobalFunction::saveFileAndGivePath($request->sticker_image);
        $item->anchor_landmark = $request->anchor_landmark ?? 'nose';
        $item->scale = $request->scale ?? 1.0;
        $item->offset_x = $request->offset_x ?? 0;
        $item->offset_y = $request->offset_y ?? 0;
        $item->position = FaceSticker::max('position') + 1;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item added successfully!');
    }
    function addReportReason(Request $request){
        $item = new ReportReasons();
        $item->title = $request->title;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Item added successfully!');
    }

    public function editWithdrawalGateway(Request $request){

        $item = RedeemGateways::where('id', $request->id)->first();
        $item->title = $request->title;
        $item->save();

        return response()->json([
            'status' => true,
            'message' => 'Item Updated Successfully',
        ]);
    }
    public function editReportReason(Request $request){

        $item = ReportReasons::where('id', $request->id)->first();
        $item->title = $request->title;
        $item->save();

        return response()->json([
            'status' => true,
            'message' => 'Item Updated Successfully',
        ]);
    }

    function deleteUserLevel(Request $request){
        $item = UserLevels::find($request->id);
        $item->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Item deleted successfully');
    }
    function deleteWithdrawalGateway(Request $request){
        $item = RedeemGateways::find($request->id);
        $item->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Item deleted successfully');
    }
    function deleteColorFilter(Request $request){
        $item = ColorFilter::find($request->id);
        GlobalFunction::deleteFile($item->image);
        $item->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Item deleted successfully');
    }
    function deleteFaceSticker(Request $request){
        $item = FaceSticker::find($request->id);
        GlobalFunction::deleteFile($item->thumbnail);
        GlobalFunction::deleteFile($item->sticker_image);
        $item->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Item deleted successfully');
    }
    function deleteReportReason(Request $request){
        $item = ReportReasons::find($request->id);
        $item->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Item deleted successfully');
    }
    function deleteOnboardingScreen(Request $request){
        $item = OnboardingScreens::find($request->id);
        GlobalFunction::deleteFile($item->image);
        $item->delete();

        return GlobalFunction::sendSimpleResponse(true, 'item deleted successfully');
    }
    function changeAndroidAdmobStatus($status){
        $settings = GlobalSettings::first();
        $settings->admob_android_status = $status;
        $settings->save();

        return GlobalFunction::sendSimpleResponse(true, 'Settings saved successfully!');
    }
    function changeiOSAdmobStatus($status){
        $settings = GlobalSettings::first();
        $settings->admob_ios_status = $status;
        $settings->save();

        return GlobalFunction::sendSimpleResponse(true, 'Settings saved successfully!');
    }


    public function updateOnboardingOrder(Request $request)
    {
        $items = OnboardingScreens::all();

        foreach ($items as $item) {
            $item->timestamps = false; // To disable update_at field updation
            $id = $item->id;

            foreach ($request->order as $order) {
                if ($order['id'] == $id) {
                    $item->position = $order['position'];
                    $item->save();
                }
            }
        }
         return response()->json(['status' => true, 'message'=> 'position updated successfully !']);
    }

    public function listColorFilters(Request $request)
    {
        $query = ColorFilter::query();
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('position', 'ASC')
                        ->get();

        $data = $result->map(function ($item) {
            $imgUrl = GlobalFunction::generateFileUrl($item->image);
            $image = "<img class='rounded' width='80' height='80' src='{$imgUrl}' alt=''>";

            $statusBadge = $item->status
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-secondary'>Inactive</span>";

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='{$item->title}'
                        data-image='{$imgUrl}'
                        data-brightness='{$item->brightness}'
                        data-contrast='{$item->contrast}'
                        data-saturation='{$item->saturation}'
                        data-warmth='{$item->warmth}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $image,
                $item->title,
                $statusBadge,
                $action
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function listFaceStickers(Request $request)
    {
        $query = FaceSticker::query();
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('position', 'ASC')
                        ->get();

        $data = $result->map(function ($item) {
            $thumbUrl = GlobalFunction::generateFileUrl($item->thumbnail);
            $thumbnail = "<img class='rounded' width='80' height='80' src='{$thumbUrl}' alt=''>";

            $stickerUrl = GlobalFunction::generateFileUrl($item->sticker_image);
            $stickerImg = "<img class='rounded' width='80' height='80' src='{$stickerUrl}' alt=''>";

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='{$item->title}'
                        data-thumbnail='{$thumbUrl}'
                        data-anchor='{$item->anchor_landmark}'
                        data-scale='{$item->scale}'
                        data-offsetx='{$item->offset_x}'
                        data-offsety='{$item->offset_y}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $thumbnail,
                $item->title,
                $stickerImg,
                $item->anchor_landmark,
                $action
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }
    public function listReportReasons(Request $request)
    {
        $query = ReportReasons::query();
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='{$item->title}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->title,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function listWithdrawalGateways(Request $request)
    {
        $query = RedeemGateways::query();
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='{$item->title}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->title,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function listUserLevels(Request $request)
    {
        $query = UserLevels::query();
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('coins_collection', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('level', 'ASC')
                        ->get();

        $data = $result->map(function ($item) {

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-level='{$item->level}'
                        data-coinscollection='{$item->coins_collection}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->level,
                $item->coins_collection,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }


    public function onboardingScreensList(Request $request)
    {
        $query = OnboardingScreens::query();
        $totalData = $query->count();

        $columns = ['id'];
        // $limit = $request->input('length');
        // $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%")
                ->orWhere('description','LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->orderBy('position', 'ASC')
                        // ->limit($limit)
                        // ->offset($start)
                        ->get();

        $data = $result->map(function ($item) {


            $imgUrl = GlobalFunction::generateFileUrl($item->image);
            $image = "<img class='rounded border' width='80' height='80' src='{$imgUrl}' alt=''>";

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='{$item->title}'
                        data-description='{$item->description}'
                        data-image='{$imgUrl}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            $title = '<span class="text-dark font-weight-bold font-16">' . $item->title . '</span><br>';
            $desc = '<span>' . $item->description . '</span>';
            $detail = $title . $desc;

            $sortable = '<div data-id='.$item->id.' class="sort-handler grabbable action-btn  d-flex align-items-center justify-content-center border rounded-2 text-info">
                <i class="uil-direction"></i>
            </div>';

            return [
                $sortable,
                $item->position,
                $image,
                $detail,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }

    public function updateOnboardingScreen(Request $request){

        $item = OnboardingScreens::where('id', $request->id)->first();

        $item->title = $request->title;
        $item->description = $request->description;
        if($request->has('image')){
            if($item->image != null){
                GlobalFunction::deleteFile($item->image);
            }
            $item->image = GlobalFunction::saveFileAndGivePath($request->image);
        }
        $item->save();

        return response()->json([
            'status' => true,
            'message' => 'Item Updated Successfully',
        ]);
    }

    public function addOnBoardingScreen(Request $request){
        $item = new OnboardingScreens();
        $item->title = $request->title;
        $item->description = $request->description;
        $item->image = GlobalFunction::saveFileAndGivePath($request->image);
        $item->position = OnboardingScreens::max('position')+1;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Onboarding Added successfully');
    }

    public function settings()
    {
        $setting = GlobalSettings::getCached();
        $baseUrl = GlobalFunction::getItemBaseUrl();
        $userType = Session::get('user_type');

         // Default values
        $packageName = '';
        $sha256 = '';
        $iosAppId = '';
        $iosPackageName = '';
        $iosTeamId = '';

         // --------------------
        // Android assetlinks.json
        // --------------------
        $assetFilePath = public_path('assets/assetlinks.json');
        if (File::exists($assetFilePath)) {
            $jsonContent = File::get($assetFilePath);
            $data = json_decode($jsonContent, true);

            if (!empty($data) && isset($data[0]['target'])) {
                $packageName = $data[0]['target']['package_name'] ?? '';
                $sha256 = isset($data[0]['target']['sha256_cert_fingerprints'])
                    ? implode(',', $data[0]['target']['sha256_cert_fingerprints'])
                    : '';
            }
        }

        // --------------------
        // iOS apple-app-site-association
        // --------------------
        $aasaFilePath = public_path('assets/apple-app-site-association');
        if (File::exists($aasaFilePath)) {
            $jsonContent = File::get($aasaFilePath);
            $data = json_decode($jsonContent, true);

            if (!empty($data) && isset($data['applinks']['details'][0]['appIDs'][0])) {

                $appId = $data['applinks']['details'][0]['appIDs'][0];
                $iosAppId = $appId;

                // Split into Team ID + Package Name
                $parts = explode('.', $appId, 2);
                if (count($parts) === 2) {
                    $iosTeamId = $parts[0];
                    $iosPackageName = $parts[1];
                }
            }
        }



        return view('settings', [
            'setting'=>$setting,
            'baseUrl'=>$baseUrl,
            'userType'=>$userType,
            'packageName' => $packageName,
            'sha256' => $sha256,
            'iosAppId' => $iosAppId,
            'iosPackageName' => $iosPackageName,
            'iosTeamId' => $iosTeamId

        ]);
    }



    public function fetchSettings()
    {
        $data = Cache::remember('api_settings_payload', 1800, function () {
            $settings = GlobalSettings::first();
            $languages = Language::all();
            $gifts = Gifts::orderBy('coin_price','DESC')->get();
            $onBoarding = OnboardingScreens::all();
            $redeemGateways = RedeemGateways::all();
            $reportReasons = ReportReasons::all();
            $colorFilters = ColorFilter::where('status', true)->orderBy('position')->get();
            $faceStickers = FaceSticker::where('status', true)->orderBy('position')->get();
            $coinPackages = CoinPackages::where('status', 1)->get();
            $dummyLives = DummyLiveVideos::where('status', 1)->with(['user:'.Constants::userPublicFields])->get();
            $userLevels = UserLevels::all();
            $musicCategories = MusicCategories::where('is_deleted', 0)->withCount('musics')->get();
            $itemBaseUrl = GlobalFunction::getItemBaseUrl();

            $settings->itemBaseUrl = $itemBaseUrl;
            $settings->languages = $languages;
            $settings->onBoarding = $onBoarding;
            $settings->coinPackages = $coinPackages;
            $settings->redeemGateways = $redeemGateways;
            $settings->reportReasons = $reportReasons;
            $settings->colorFilters = $colorFilters;
            $settings->faceStickers = $faceStickers;
            $settings->gifts = $gifts;
            $settings->musicCategories = $musicCategories;
            $settings->userLevels = $userLevels;
            $settings->dummyLives = $dummyLives;

            return $settings;
        });

        return response()->json([
            'status' => true,
            'message' => 'Settings Fetched',
            'data' => $data,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }
        if ($request->has('app_name')) {
            $setting->app_name = $request->app_name;
            $request->session()->put('app_name', $setting['app_name']);
        }
        if ($request->has('currency')) {
            $setting->currency = $request->currency;
        }
        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            GlobalFunction::saveFileInLocal($file, 'favicon.png');
        }

        if ($request->hasFile('logo_dark')) {
            $file = $request->file('logo_dark');
            GlobalFunction::saveFileInLocal($file, 'logo-dark.png');
        }
        if ($request->hasFile('logo_light')) {
            $file = $request->file('logo_light');
            GlobalFunction::saveFileInLocal($file, 'logo.png');
        }

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveLimitSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->max_upload_daily = $request->max_upload_daily;
        $setting->max_comment_daily = $request->max_comment_daily;
        $setting->max_comment_reply_daily = $request->max_comment_reply_daily;
        $setting->max_story_daily = $request->max_story_daily;
        $setting->max_comment_pins = $request->max_comment_pins;
        $setting->max_post_pins = $request->max_post_pins;
        $setting->max_user_links = $request->max_user_links;
        $setting->max_images_per_post = $request->max_images_per_post;

        // Creator Monetization
        if ($request->has('ecpm_rate')) {
            $setting->ecpm_rate = (float) $request->ecpm_rate;
        }
        if ($request->has('creator_revenue_share')) {
            $setting->creator_revenue_share = (int) $request->creator_revenue_share;
        }

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveDeeplinkSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->app_store_download_link = $request->app_store_download_link;
        $setting->play_store_download_link = $request->play_store_download_link;
        $setting->uri_scheme = $request->uri_scheme;

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveLiveStreamSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->live_dummy_show = $request->live_dummy_show;
        $setting->live_battle = $request->live_battle;
        $setting->min_followers_for_live = $request->min_followers_for_live;
        $setting->live_min_viewers = $request->live_min_viewers;
        $setting->live_timeout = $request->live_timeout;
        $setting->livekit_host = $request->livekit_host;
        $setting->livekit_api_key = $request->livekit_api_key;
        $setting->livekit_api_secret = $request->livekit_api_secret;

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveCameraEffectSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->is_camera_effects = $request->is_camera_effects;
        $setting->snap_camera_kit_app_id = $request->snap_camera_kit_app_id;
        $setting->snap_camera_kit_api_token = $request->snap_camera_kit_api_token;
        $setting->snap_camera_kit_group_id = $request->snap_camera_kit_group_id;

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveGIFSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->gif_support = $request->gif_support;
        $setting->giphy_key = $request->giphy_key;

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }
    public function saveBasicSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->currency = $request->currency;
        $setting->coin_value = $request->coin_value;
        $setting->min_redeem_coins = $request->min_redeem_coins;

        $setting->is_compress = $request->is_compress;
        $setting->is_withdrawal_on = $request->is_withdrawal_on;
        $setting->registration_bonus_status = $request->registration_bonus_status;
        $setting->registration_bonus_amount = $request->registration_bonus_amount;

        if ($request->has('help_mail')) {
            $setting->help_mail = $request->help_mail;
        }

        $setting->watermark_status = $request->watermark_status;
        if($request->has('watermark_image')){
            if($setting->watermark_image!= null){
                GlobalFunction::deleteFile($setting->watermark_image);
            }
            $setting->watermark_image = GlobalFunction::saveFileAndGivePath($request->watermark_image);
        }

        // Email verification toggle
        if ($request->has('email_verification_enabled')) {
            $setting->email_verification_enabled = $request->email_verification_enabled;
        }

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }

    public function saveSmtpSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->smtp_host = $request->smtp_host;
        $setting->smtp_port = $request->smtp_port;
        $setting->smtp_username = $request->smtp_username;
        if ($request->smtp_password) {
            $setting->smtp_password = $request->smtp_password;
        }
        $setting->smtp_encryption = $request->smtp_encryption;
        $setting->smtp_from_email = $request->smtp_from_email;
        $setting->smtp_from_name = $request->smtp_from_name;
        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'SMTP Settings Updated Successfully',
        ]);
    }

    public function saveContentModerationSettings(Request $request)
    {
        $setting = GlobalSettings::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting Not Found',
            ]);
        }

        $setting->is_content_moderation = $request->is_content_moderation;
        $setting->moderation_cloudflare_url = $request->moderation_cloudflare_url;
        $setting->moderation_cloudflare_token = $request->moderation_cloudflare_token;
        $setting->moderation_self_hosted_url = $request->moderation_self_hosted_url;

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Setting Updated Successfully',
        ]);

    }

    public function changePassword(Request $request)
    {
        $adminUser = Admin::where('user_type', $request->user_type)->first();
        if (!$adminUser) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found',
            ]);
        }
        if(Session::get('user_type')!= 1){
          return response()->json([
                    'status' => false,
                    'message' => 'Password change not possible!',
                ]);
        }
        if ($request->has('old_password')) {
            if (decrypt($adminUser->admin_password) != $request->old_password) {
                return response()->json([
                    'status' => false,
                    'message' => 'Old Password does not match',
                ]);
            }
            if (decrypt($adminUser->admin_password)  == $request->old_password) {
                $adminUser->admin_password = Crypt::encrypt($request->new_password);
                $adminUser->save();

                $request->session()->put('userpassword', $request->new_password);

                return response()->json([
                    'status' => true,
                    'message' => 'Change Password',
                ]);
            }
        }
    }

    public function admobSettingSave(Request $request)
    {
        $admobSetting = GlobalSettings::first();
        if (!$admobSetting) {
            return response()->json([
                'status' => false,
                'message' => 'Record Not Found',
            ]);
        }

        $admobSetting->admob_banner = $request->admob_banner;
        $admobSetting->admob_int = $request->admob_int;
        $admobSetting->admob_banner_ios = $request->admob_banner_ios;
        $admobSetting->admob_int_ios = $request->admob_int_ios;

        // App Open Ad
        $admobSetting->app_open_ad_enabled = $request->has('app_open_ad_enabled');
        $admobSetting->admob_app_open_android = $request->admob_app_open_android;
        $admobSetting->admob_app_open_ios = $request->admob_app_open_ios;

        // Part Transition Ads
        $admobSetting->part_transition_ad_enabled = $request->has('part_transition_ad_enabled');
        $admobSetting->part_transition_ad_start_at = (int) ($request->part_transition_ad_start_at ?? 3);
        $admobSetting->part_transition_ad_interval = (int) ($request->part_transition_ad_interval ?? 2);

        // Custom App Open Ad
        $admobSetting->custom_app_open_ad_enabled = $request->has('custom_app_open_ad_enabled');
        $admobSetting->custom_app_open_ad_post_id = $request->custom_app_open_ad_post_id ?: null;
        $admobSetting->custom_app_open_ad_skip_seconds = (int) ($request->custom_app_open_ad_skip_seconds ?? 5);
        $admobSetting->custom_app_open_ad_url = $request->custom_app_open_ad_url ?: null;

        // VAST Pre-Roll Ads
        $admobSetting->ima_preroll_enabled = (bool) $request->input('ima_preroll_enabled', false);
        $admobSetting->ima_preroll_min_video_length = (int) ($request->ima_preroll_min_video_length ?? 0);
        $admobSetting->ima_preroll_ad_tag_android = $request->ima_preroll_ad_tag_android;
        $admobSetting->ima_preroll_ad_tag_ios = $request->ima_preroll_ad_tag_ios;

        // VAST Mid-Roll Ads
        $admobSetting->ima_midroll_enabled = (bool) $request->input('ima_midroll_enabled', false);
        $admobSetting->ima_midroll_min_video_length = (int) ($request->ima_midroll_min_video_length ?? 0);
        $admobSetting->ima_midroll_ad_tag_android = $request->ima_midroll_ad_tag_android;
        $admobSetting->ima_midroll_ad_tag_ios = $request->ima_midroll_ad_tag_ios;

        // VAST Post-Roll Ads
        $admobSetting->ima_postroll_enabled = (bool) $request->input('ima_postroll_enabled', false);
        $admobSetting->ima_postroll_min_video_length = (int) ($request->ima_postroll_min_video_length ?? 0);
        $admobSetting->ima_postroll_ad_tag_android = $request->ima_postroll_ad_tag_android;
        $admobSetting->ima_postroll_ad_tag_ios = $request->ima_postroll_ad_tag_ios;

        // Preload Settings
        $admobSetting->ima_preload_seconds_before = (int) ($request->ima_preload_seconds_before ?? 10);

        // VAST Feed Video Ads
        $admobSetting->vast_feed_ad_enabled = (bool) $request->input('vast_feed_ad_enabled', false);
        $admobSetting->vast_feed_ad_tag_android = $request->vast_feed_ad_tag_android;
        $admobSetting->vast_feed_ad_tag_ios = $request->vast_feed_ad_tag_ios;

        // Native Feed Ads (fallback when VAST is disabled)
        $admobSetting->native_ad_feed_enabled = (bool) $request->input('native_ad_feed_enabled', false);
        $admobSetting->admob_native_android = $request->admob_native_android;
        $admobSetting->admob_native_ios = $request->admob_native_ios;
        $admobSetting->native_ad_min_interval = (int) ($request->native_ad_min_interval ?? 4);
        $admobSetting->native_ad_max_interval = (int) ($request->native_ad_max_interval ?? 8);

        $admobSetting->save();

        return response()->json([
            'status' => true,
            'message' => 'Admob Updated Successfully',
        ]);
    }

    public function updatePrivacyAndTerms(Request $request)
    {
        $setting = GlobalSettings::first();

        if ($request->has('privacy_policy')) {
            $setting->privacy_policy = $request->privacy_policy;
        }

        if ($request->has('terms_of_uses')) {
            $setting->terms_of_uses = $request->terms_of_uses;
        }

        $setting->save();

        return response()->json([
            'status' => true,
            'message' => 'Update successful',
        ]);
    }

    function privacy_policy()
    {
        $setting = GlobalSettings::first();

        return view('privacy_policy', [
            'data' => $setting->privacy_policy
        ]);
    }
    function terms_of_uses()
    {
        $setting = GlobalSettings::first();

        return view('terms_of_uses', [
            'data' => $setting->terms_of_uses
        ]);
    }

    function community_guidelines()
    {
        $setting = GlobalSettings::first();

        return view('privacy_policy', [
            'data' => $setting->community_guidelines
        ]);
    }

    public function imageUploadInEditor(Request $request)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('editor', 'public'); // Save image in 'public/storage/images'
            return response()->json(['imagePath' => $path]);
        }
        return response()->json(['error' => 'No image uploaded'], 400);
    }


    public function uploadFileGivePath(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $path = GlobalFunction::saveFileAndGivePath($request->file('file'));

        return response()->json([
            'status' => true,
            'message' => "file uploaded, here is the path!",
            'data' => $path,
        ]);
    }
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filePath' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        GlobalFunction::deleteFile($request->filePath);

        return response()->json([
            'status' => true,
            'message' => "file deleted successfully!",
        ]);
    }

    public function saveMonetizationSettings(Request $request)
    {
        $settings = GlobalSettings::first();
        if (!$settings) {
            return response()->json(['status' => false, 'message' => 'Settings not found']);
        }

        $settings->commission_percentage = $request->commission_percentage;
        $settings->min_followers_for_monetization = $request->min_followers_for_monetization;
        $settings->reward_coins_per_ad = $request->reward_coins_per_ad;
        $settings->max_rewarded_ads_daily = $request->max_rewarded_ads_daily;
        $settings->admob_rewarded_android = $request->admob_rewarded_android;
        $settings->admob_rewarded_ios = $request->admob_rewarded_ios;
        $settings->save();

        Cache::forget('global_settings');

        return response()->json(['status' => true, 'message' => 'Monetization settings saved successfully']);
    }

    public function saveAdNetworkSettings(Request $request)
    {
        $settings = GlobalSettings::first();
        if (!$settings) {
            return response()->json(['status' => false, 'message' => 'Settings not found']);
        }

        // Meta Audience Network
        $settings->meta_ads_enabled = $request->meta_ads_enabled ?? false;
        $settings->meta_banner_android = $request->meta_banner_android;
        $settings->meta_banner_ios = $request->meta_banner_ios;
        $settings->meta_interstitial_android = $request->meta_interstitial_android;
        $settings->meta_interstitial_ios = $request->meta_interstitial_ios;
        $settings->meta_rewarded_android = $request->meta_rewarded_android;
        $settings->meta_rewarded_ios = $request->meta_rewarded_ios;

        // Unity Ads
        $settings->unity_ads_enabled = $request->unity_ads_enabled ?? false;
        $settings->unity_game_id_android = $request->unity_game_id_android;
        $settings->unity_game_id_ios = $request->unity_game_id_ios;
        $settings->unity_banner_android = $request->unity_banner_android;
        $settings->unity_banner_ios = $request->unity_banner_ios;
        $settings->unity_interstitial_android = $request->unity_interstitial_android;
        $settings->unity_interstitial_ios = $request->unity_interstitial_ios;
        $settings->unity_rewarded_android = $request->unity_rewarded_android;
        $settings->unity_rewarded_ios = $request->unity_rewarded_ios;

        // AppLovin
        $settings->applovin_enabled = $request->applovin_enabled ?? false;
        $settings->applovin_sdk_key = $request->applovin_sdk_key;
        $settings->applovin_banner_android = $request->applovin_banner_android;
        $settings->applovin_banner_ios = $request->applovin_banner_ios;
        $settings->applovin_interstitial_android = $request->applovin_interstitial_android;
        $settings->applovin_interstitial_ios = $request->applovin_interstitial_ios;
        $settings->applovin_rewarded_android = $request->applovin_rewarded_android;
        $settings->applovin_rewarded_ios = $request->applovin_rewarded_ios;

        // Waterfall priorities
        $settings->waterfall_banner_priority = json_encode($request->waterfall_banner_priority ?? ['admob']);
        $settings->waterfall_interstitial_priority = json_encode($request->waterfall_interstitial_priority ?? ['admob']);
        $settings->waterfall_rewarded_priority = json_encode($request->waterfall_rewarded_priority ?? ['admob']);

        // IMA/VAST Pre-Roll
        $settings->ima_preroll_enabled = $request->ima_preroll_enabled ?? false;
        $settings->ima_preroll_frequency = $request->ima_preroll_frequency ?? 0;
        $settings->ima_ad_tag_android = $request->ima_ad_tag_android;
        $settings->ima_ad_tag_ios = $request->ima_ad_tag_ios;
        $settings->ima_preroll_min_video_length = $request->ima_preroll_min_video_length ?? 0;

        // IMA/VAST Mid-Roll
        $settings->ima_midroll_enabled = $request->ima_midroll_enabled ?? false;
        $settings->ima_midroll_frequency = $request->ima_midroll_frequency ?? 0;
        $settings->ima_midroll_ad_tag_android = $request->ima_midroll_ad_tag_android;
        $settings->ima_midroll_ad_tag_ios = $request->ima_midroll_ad_tag_ios;
        $settings->ima_midroll_min_video_length = $request->ima_midroll_min_video_length ?? 30;

        // IMA/VAST Post-Roll
        $settings->ima_postroll_enabled = $request->ima_postroll_enabled ?? false;
        $settings->ima_postroll_frequency = $request->ima_postroll_frequency ?? 0;
        $settings->ima_postroll_ad_tag_android = $request->ima_postroll_ad_tag_android;
        $settings->ima_postroll_ad_tag_ios = $request->ima_postroll_ad_tag_ios;
        $settings->ima_postroll_min_video_length = $request->ima_postroll_min_video_length ?? 15;

        // Preload timing
        $settings->ima_preload_seconds_before = $request->ima_preload_seconds_before ?? 10;

        // VAST Feed Video Ads
        $settings->vast_feed_ad_enabled = $request->vast_feed_ad_enabled ?? false;
        $settings->vast_feed_ad_tag_android = $request->vast_feed_ad_tag_android;
        $settings->vast_feed_ad_tag_ios = $request->vast_feed_ad_tag_ios;

        $settings->save();
        Cache::forget('global_settings');

        return response()->json(['status' => true, 'message' => 'Ad network settings saved successfully']);
    }

    /**
     * Save Instagram import settings (admin panel).
     */
    public function saveInstagramSettings(Request $request)
    {
        $settings = GlobalSettings::first();

        $settings->instagram_import_enabled = $request->instagram_import_enabled ?? false;
        $settings->instagram_app_id = $request->instagram_app_id;
        $settings->instagram_app_secret = $request->instagram_app_secret;
        $settings->instagram_redirect_uri = $request->instagram_redirect_uri;

        $settings->save();
        Cache::forget('global_settings');

        return response()->json(['status' => true, 'message' => 'Instagram settings saved successfully']);
    }
}
