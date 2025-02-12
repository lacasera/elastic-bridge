<?php

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Room::fake($this->getFakeData());
    }

    /**
     * @return void
     *
     * @test
     */
    public function it_should_throw_a_missing_term_level_query_exception()
    {
        $this->expectException(MissingTermLevelQuery::class);

        Room::matchAll(2.0)->toQuery();
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_match_all_query()
    {
        $actual = Room::asRaw()->matchAll(2.0)->toQuery();

        $expected = [
            'query' => [
                'must' => [
                    'match_all' => [
                        'boost' => 2.0,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_should_match_all_query()
    {
        $actual = Room::asRaw()->shouldMatchAll()->toQuery();

        $expected = [
            'query' => [
                'should' => [
                    'match_all' => [
                        'boost' => 1.0,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_must_match_query()
    {
        $actual = Room::asBoolean()->mustMatch('currency', 'usd')->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'currency' => [
                                'query' => 'usd',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_raw_query()
    {
        $actual = Room::asRaw()->raw([
            'bool' => [
                'must' => [
                    'match' => [
                        'code' => 'xoxo',
                    ],
                ],
            ],
        ])->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'code' => 'xoxo',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_must_exist_query()
    {
        $actual = Room::asRaw()->mustExist('currency')->toQuery();

        $expected = [
            'query' => [
                'must' => [
                    'exists' => [
                        'field' => 'currency',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_should_exist_query()
    {
        $actual = Room::asRaw()->shouldExist('currency')->toQuery();

        $expected = [
            'query' => [
                'should' => [
                    'exists' => [
                        'field' => 'currency',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_match_query()
    {
        $actual = Room::query()
            ->asRaw()
            ->match('description', 'foo bar')
            ->toQuery();

        $expected = [
            'query' => [
                'match' => [
                    'description' => [
                        'query' => 'foo bar',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_set_values_for_a_query_with_values()
    {
        $actual = Room::asIds()->withValues(['1'])->toQuery();

        $expected = [
            'query' => [
                'ids' => [
                    'values' => [1],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     *
     * @test
     */
    public function can_build_a_fuzzy_query()
    {
        $actual = Room::query()
            ->asFuzzy()
            ->withValues('xoxo', 'currency', [
                'fuzziness' => 0.5,
                'boost' => 1,
                'prefix_length' => 1,
            ])
            ->toQuery();

        $expected = [
            'query' => [
                'fuzzy' => [
                    'currency' => [
                        'values' => 'xoxo',
                        'fuzziness' => 0.5,
                        'boost' => 1,
                        'prefix_length' => 1,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
