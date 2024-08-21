<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Traits;

trait HasFilters
{
    /**
     * @return $this
     */
    public function filterByTerm(string $field, $value)
    {
        $this->query->setFilter(type: 'term', field: $field, value: $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function filterByRange(string $field, $value, $operator)
    {
        $this->query->setFilter('range', $field, $value, $operator);

        return $this;
    }

    /**
     * @return $this
     */
    public function filterByGeoShape(string $field, array $coordinates, string $relation = 'envelope', array $options = [])
    {
        $this->query->setRawFilters([
            'geo_shape' => [
                $field => [
                    'shape' => [
                        'type' => 'envelope',
                        'coordinates' => $coordinates,
                    ],
                    'relation' => $relation,
                    ...$options,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function filterByGeoDistance(string $field, float $distance, float $latitude, float $longitude, string $distanceType = 'arc', array $options = [])
    {
        $this->query->setRawFilters([
            'geo_distance' => [
                'distance' => $distance,
                'distance_type' => $distanceType,
                $field => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                ],
                ...$options,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function filterByGeoPolygon(string $field, array $points, array $options = [])
    {
        $this->query->setRawFilters([
            'geo_polygon' => [
                $field => [
                    'points' => $points,
                ],
                ...$options,
            ],
        ]);

        return $this;
    }

    /**
     * @return void
     */
    public function filterByGeoDistanceRange(
        string $field,
        float $from,
        float $to,
        float $latitude,
        float $longitude,
        string $unit = 'km',
        $options = []
    ) {
        $this->query->setRawFilters([
            'geo_distance_range' => [
                'from' => $from,
                'to' => $to,
                'distance_unit' => $unit,
                $field => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                ],
                ...$options,
            ],
        ]);
    }

    /**
     * @return $this
     */
    public function filterByGeoBoundingBox(string $field, array $topLeft, array $bottomRight, array $options = [])
    {
        $this->query->setRawFilters([
            'geo_bounding_box' => [
                $field => [
                    'top_left' => $topLeft,
                    'bottom_right' => $bottomRight,
                ],
                ...$options,
            ],
        ]);

        return $this;
    }
}
