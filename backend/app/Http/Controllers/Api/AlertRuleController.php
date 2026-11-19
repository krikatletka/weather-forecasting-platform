<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Models\AlertRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = AlertRule::where('user_id', $request->user()->id)
            ->with('city:id,name')
            ->latest('id')
            ->get();

        return response()->json(['data' => $rules]);
    }

    public function store(StoreAlertRuleRequest $request): JsonResponse
    {
        $rule = AlertRule::create($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json(['data' => $rule->load('city:id,name')], 201);
    }

    public function update(UpdateAlertRuleRequest $request, int $id): JsonResponse
    {
        $rule = $this->findOwned($request, $id);
        $rule->update($request->validated());

        return response()->json(['data' => $rule->fresh('city:id,name')]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->findOwned($request, $id)->delete();

        return response()->json(null, 204);
    }

    /** Чужі й неіснуючі правила дають однаковий 404. */
    private function findOwned(Request $request, int $id): AlertRule
    {
        return AlertRule::where('user_id', $request->user()->id)->findOrFail($id);
    }
}