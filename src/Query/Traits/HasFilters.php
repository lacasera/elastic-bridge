<?php

namespace Lacasera\ElasticBridge\Query\Traits;

trait HasFilters
{
    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function filterByTerm(string $field, $value)
    {
        $this->query->setFilter(type: 'term', field: $field, value: $value);

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param $operator
     * @return $this
     */
    public function filterByRange(string $field, $value, $operator)
    {
        $this->query->setFilter('range', $field, $value, $operator);

        return $this;
    }

    /**
     * @param string $field
     * @param array $coordinates
     * @param string $relation
     * @param array $options
     * @return $this
     */
    public function filterByGeoShape(string $field, array $coordinates, string $relation = 'envelope', array $options = [])
    {
        $this->query->setRawFilters([
            'geo_shape' => [
                $field => [
                    'shape' => [
                        'type' => 'envelope',
                        'coordinates' => $coordinates
                    ],
                    'relation' => $relation,
                    ...$options
                ]
            ]
        ]);

        return $this;
    }

    /**
     * @param string $field
     * @param float $distance
     * @param float $latitude
     * @param float $longitude
     * @param string $distanceType
     * @param array $options
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
                    'lon' => $longitude
                ],
                ...$options,
            ]
        ]);

        return $this;
    }

    /**
     * @param string $field
     * @param array $points
     * @param array $options
     * @return $this
     */
    public function filterByGeoPolygon(string $field, array $points, array $options = [])
    {
        $this->query->setRawFilters([
            "geo_polygon" => [
                $field => [
                    'points' => $points
                ],
                ...$options
            ]
        ]);

        return $this;
    }

    /**
     * @param string $field
     * @param float $from
     * @param float $to
     * @param float $latitude
     * @param float $longitude
     * @param string $unit
     * @param $options
     * @return void
     */
    public function filterByGeoDistanceRange(string $field, float $from, float $to,  float $latitude, float $longitude, string $unit = 'km', $options = [])
    {
        $this->query->setRawFilters([
            'geo_distance_range' => [
                'from' => $from,
                'to' => $to,
                'distance_unit' => $unit,
                $field => [
                    'lat' => $latitude,
                    'lon' => $longitude
                ],
                ...$options
            ]
        ]);
    }

    /**
     * @param string $field
     * @param array $topLeft
     * @param array $bottomRight
     * @param array $options
     * @return $this
     */
    public function filterByGeoBoundingBox(string $field, array $topLeft, array $bottomRight, array $options = [])
    {
        $this->query->setRawFilters([
            'geo_bounding_box' => [
                $field => [
                    'top_left' => $topLeft,
                    'bottom_right' => $bottomRight
                ],
                ...$options
            ]
        ]);
        return $this;
    }
}
