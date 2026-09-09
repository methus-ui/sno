<?php

namespace App\Http\Controllers\Admin\Zone;

use App\Http\Controllers\Controller;
use App\Models\WeatherSurgeRule;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeatherSurgeController extends Controller
{
    public function index(int $zoneId): View|RedirectResponse
    {
        $zone = Zone::find($zoneId);
        if (!$zone) {
            Toastr::error(translate('messages.zone_not_found'));
            return redirect()->route('admin.business-settings.zone.home');
        }

        $rules = WeatherSurgeRule::where('zone_id', $zoneId)
            ->orWhereNull('zone_id')
            ->orderBy('weather_condition')
            ->get();

        $conditions = ['rain', 'heavy_rain', 'storm', 'snow', 'extreme_heat', 'fog'];

        return view('admin-views.zone.weather-surge.index', compact('zone', 'rules', 'conditions'));
    }

    public function store(Request $request, int $zoneId): RedirectResponse
    {
        $request->validate([
            'weather_condition' => 'required|in:rain,heavy_rain,storm,snow,extreme_heat,fog',
            'surge_percentage' => 'required|numeric|min:0|max:500',
            'message' => 'nullable|string|max:255',
            'min_temp' => 'nullable|numeric',
            'max_temp' => 'nullable|numeric',
        ]);

        WeatherSurgeRule::create([
            'zone_id' => $zoneId,
            'weather_condition' => $request->weather_condition,
            'surge_percentage' => $request->surge_percentage,
            'min_temp' => $request->min_temp,
            'max_temp' => $request->max_temp,
            'is_enabled' => true,
            'message' => $request->message,
        ]);

        Toastr::success(translate('messages.weather_surge_rule_created'));
        return back();
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'weather_condition' => 'required|in:rain,heavy_rain,storm,snow,extreme_heat,fog',
            'surge_percentage' => 'required|numeric|min:0|max:500',
            'message' => 'nullable|string|max:255',
            'min_temp' => 'nullable|numeric',
            'max_temp' => 'nullable|numeric',
        ]);

        $rule = WeatherSurgeRule::findOrFail($id);
        $rule->update([
            'weather_condition' => $request->weather_condition,
            'surge_percentage' => $request->surge_percentage,
            'min_temp' => $request->min_temp,
            'max_temp' => $request->max_temp,
            'message' => $request->message,
        ]);

        Toastr::success(translate('messages.weather_surge_rule_updated'));
        return back();
    }

    public function updateStatus(int $id, int $status): RedirectResponse
    {
        WeatherSurgeRule::findOrFail($id)->update(['is_enabled' => $status]);
        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        WeatherSurgeRule::findOrFail($id)->delete();
        Toastr::success(translate('messages.weather_surge_rule_deleted'));
        return back();
    }
}
