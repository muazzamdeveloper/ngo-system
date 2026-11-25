<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyRequest;
use App\Models\CardSetting;
use App\Models\Member;
use App\Services\MemberDocumentService;
use App\Support\IndianStates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/** The public membership application page. */
class ApplicationController extends Controller
{
    public function create(): View
    {
        $setting = CardSetting::current();

        return view('apply.create', [
            'setting' => $setting,
            'feeLabel' => $setting->feeLabel(),
            'hasQr' => $setting->hasImage('payment_qr'),
            'states' => IndianStates::ALL,
            'genders' => Member::GENDERS,
            'bloodGroups' => Member::BLOOD_GROUPS,
            'org' => \App\Models\OrganizationSetting::current(),
        ]);
    }

    public function store(ApplyRequest $request, MemberDocumentService $documents): RedirectResponse
    {
        // Hidden trap field: people never see it, simple bots fill it. Pretend it worked, save nothing.
        if ($request->filled('website')) {
            return redirect()->route('apply.thanks')->with('applied', true);
        }

        $data = $request->validated();

        $photoPath = $request->file('photo')->store('members/photos', 'local');
        $documentPaths = $documents->storeUploads($request);

        try {
            DB::transaction(function () use ($data, $photoPath, $documentPaths) {
                $member = new Member();
                $member->fill(Arr::only($data, [
                    'date_of_birth', 'gender', 'blood_group', 'mobile', 'whatsapp', 'email',
                    'address', 'district', 'work_area', 'state',
                ]));
                $member->full_name = trim($data['first_name'] . ' ' . $data['last_name']);
                // The form has no separate City box: the district is used. Optional boxes left empty are stored as ''.
                $member->city = $data['district'];
                $member->father_name = $data['father_name'] ?? '';
                $member->pincode = $data['pincode'] ?? '';
                $member->photo_path = $photoPath;
                $member->forceFill($documentPaths);
                $member->setAadhaar($data['aadhaar']);
                $member->source = Member::SOURCE_ONLINE;
                $member->save(); // status defaults to "pending"; created_by stays empty
            });
        } catch (UniqueConstraintViolationException) {
            // Two identical applications arrived at the same moment.
            $documents->delete([$photoPath, ...array_values($documentPaths)]);

            return back()->withInput(request()->except('aadhaar'))->withErrors(['form' => ApplyRequest::DUPLICATE_MESSAGE]);
        } catch (Throwable $e) {
            $documents->delete([$photoPath, ...array_values($documentPaths)]);

            throw $e;
        }

        return redirect()->route('apply.thanks')->with('applied', true);
    }

    public function thanks(): View|RedirectResponse
    {
        if (! session()->has('applied')) {
            return redirect()->route('apply.create');
        }

        return view('apply.thanks');
    }
}
