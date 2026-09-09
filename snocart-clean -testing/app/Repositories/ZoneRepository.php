<?php

namespace App\Repositories;

use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\Point;


class ZoneRepository implements ZoneRepositoryInterface
{
    public function __construct(protected Zone $zone)
    {
    }

    public function add(array $data): string|object
    {
        $zone = $this->zone->newInstance();
        foreach ($data as $key => $column) {
            $zone[$key] = $column;
        }
        $zone->save();
        return $zone;
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->zone->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->zone->get();
    }

    public function getListWhere(string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        $key = explode(' ', $searchValue);

        return $this->zone->withCount(['stores','deliverymen'])

            ->when(isset($key) , function($q) use($key){
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->paginate($dataLimit);
    }

    public function update(string $id, array $data): bool|string|object
    {
        $zone = $this->zone->find($id);
        foreach ($data as $key => $column) {
            $zone[$key] = $column;
        }
        $zone->save();
        return $zone;
    }

    public function delete(string $id): bool
    {
        $zone = $this->zone->find($id);
        $zone->translations()->delete();
        $zone->delete();

        return true;
    }

    public function getFirstWithoutGlobalScopeWhere(array $params, array $relations = []): ?Model
    {
        return $this->zone->withoutGlobalScope('translate')->where($params)->first();
    }

    public function getAll(): Collection
    {
        return $this->zone->all();
    }

public function getWithCoordinateWhere(array $params): ?Model
{
$zone = $this->zone->withoutGlobalScopes()
    ->where($params)
    ->first();

if ($zone && $zone->coordinates instanceof Polygon) {
    // The Polygon constructor takes an array of LineString objects
    // So we can use `->toArray()` to unpack its structure manually
    $geometryData = $zone->coordinates->toArray();
//dd($geometryData);
    // MySQL stores a Polygon as an array of LineStrings → array of Points
    // So we get the first ring (outer boundary)
    $pointsData = $geometryData['coordinates'] ?? []; // array of ['lat' => ..., 'lng' => ...]
$pointsData=$pointsData[0];
//dd(($pointsData));
if (count($pointsData)) {
        // Average the lat (index 0) and lng (index 1)
        $lat = collect($pointsData)->avg(fn($point) => $point[0]);
//dd($lat);
        $lng = collect($pointsData)->avg(fn($point) => $point[1]);

        $center = new Point($lat, $lng, 4326);
        $zone->center = $center;
 $zone->center = "POINT($lat $lng)";

        //dd($center); // or return $center;
    } else {
        dd('Polygon is empty');
    }
} else {
    dd('Zone not found or no coordinates');
}
//dd(trim(explode(' ',$zone->center)[1], 'POINT()'));
return $zone;
    //dd($zone);
}

    public function getExportList(Request $request): Collection
    {
        $key = explode(' ', $request['search']);
        return $this->zone->withCount(['stores','deliverymen'])
            ->when(isset($key) , function($q) use($key){
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->get();
    }

    public function getLatest(array $relations = []): ?Model
    {
        return $this->zone->with($relations)->latest()->first();
    }

    public function zoneModuleSetupUpdate(string $id, array $data, array $moduleData): bool|string|object
    {
        $zone = $this->zone->find($id);
        foreach ($data as $key => $column) {
            $zone[$key] = $column;
        }
        $zone->modules()->sync($moduleData);
        $zone->save();
        return $zone;
    }

    public function getWithCountLatest(array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->zone->withCount($relations)->latest()->paginate($dataLimit);
    }

    public function getActiveListExcept(array $params): Collection
    {
        return $this->zone->whereNot($params)->active()->get();
    }
}
