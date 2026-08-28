<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferralRequest;
use App\Models\Referral;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Referral::class);

        return Inertia::render('Referrals/Index', [
            'referrals' => Referral::query()
                ->with(['referrer:id,name,code', 'referred:id,name,code'])
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Referral::class);

        return Inertia::render('Referrals/Create', [
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReferralRequest $request): RedirectResponse
    {
        Referral::create($request->validated());

        return to_route('referrals.index')->with('success', 'Referido registrado correctamente.');
    }
}
