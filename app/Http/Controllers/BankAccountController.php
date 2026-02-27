<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\GlobalFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    public function fetchBankAccounts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $accounts = BankAccount::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'bank accounts fetched', $accounts);
    }

    public function addBankAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'gateway' => 'required|string|max:100',
            'account_details' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $isDefault = $request->input('is_default', false);

        // If setting as default, unset others
        if ($isDefault) {
            BankAccount::where('user_id', $user->id)->update(['is_default' => false]);
        }

        // If first account, make it default
        $existingCount = BankAccount::where('user_id', $user->id)->count();
        if ($existingCount === 0) {
            $isDefault = true;
        }

        $account = BankAccount::create([
            'user_id' => $user->id,
            'label' => $request->input('label'),
            'gateway' => $request->gateway,
            'account_holder_name' => $request->input('account_holder_name'),
            'account_details' => $request->account_details,
            'is_default' => $isDefault,
        ]);

        return GlobalFunction::sendDataResponse(true, 'bank account added', $account);
    }

    public function updateBankAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'id' => 'required|exists:tbl_bank_accounts,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $account = BankAccount::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return GlobalFunction::sendSimpleResponse(false, 'bank account not found');
        }

        if ($request->has('label')) $account->label = $request->label;
        if ($request->has('gateway')) $account->gateway = $request->gateway;
        if ($request->has('account_holder_name')) $account->account_holder_name = $request->account_holder_name;
        if ($request->has('account_details')) $account->account_details = $request->account_details;

        if ($request->input('is_default', false)) {
            BankAccount::where('user_id', $user->id)->update(['is_default' => false]);
            $account->is_default = true;
        }

        $account->save();

        return GlobalFunction::sendDataResponse(true, 'bank account updated', $account);
    }

    public function deleteBankAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'id' => 'required|exists:tbl_bank_accounts,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $account = BankAccount::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return GlobalFunction::sendSimpleResponse(false, 'bank account not found');
        }

        $wasDefault = $account->is_default;
        $account->delete();

        // If deleted account was default, set first remaining as default
        if ($wasDefault) {
            $nextAccount = BankAccount::where('user_id', $user->id)->first();
            if ($nextAccount) {
                $nextAccount->is_default = true;
                $nextAccount->save();
            }
        }

        return GlobalFunction::sendSimpleResponse(true, 'bank account deleted');
    }

    public function setDefaultBankAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'id' => 'required|exists:tbl_bank_accounts,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $account = BankAccount::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return GlobalFunction::sendSimpleResponse(false, 'bank account not found');
        }

        BankAccount::where('user_id', $user->id)->update(['is_default' => false]);
        $account->is_default = true;
        $account->save();

        return GlobalFunction::sendSimpleResponse(true, 'default bank account set');
    }
}
