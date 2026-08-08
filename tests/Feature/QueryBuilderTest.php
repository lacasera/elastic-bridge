<?php

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class QueryBuilderTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Room::fake($this->getFakeData());
    }

    #[Test]
    public function it_should_throw_a_missing_term_level_query_exception(): void
    {
        $this->expectException(MissingTermLevelQuery::class);

        Room::matchAll(2.0)->toQuery();
    }

    #[Test]
    public function can_build_match_all_query(): void
    {
        $actual = Room::asRaw()->matchAll(2.0)->toQuery();

        $expected = [
            'query' => [
                'must' => [
                    ['match_all' => [
                        'boost' => 2.0,
                    ]],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_build_a_should_match_all_query(): void
    {
        $actual = Room::asRaw()->shouldMatchAll()->toQuery();

        $expected = [
            'query' => [
                'should' => [
                    ['match_all' => [
                        'boost' => 1.0,
                    ]],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_build_a_must_match_query(): void
    {
        $actual = Room::asBoolean()->mustMatch('currency', 'usd')->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['match' => [
                            'currency' => [
                                'query' => 'usd',
                            ],
                        ]],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_appends_multiple_must_clauses_as_an_array(): void
    {
        $actual = Room::asBoolean()
            ->mustMatch('currency', 'usd')
            ->mustMatch('code', 'xoxo')
            ->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['match' => ['currency' => ['query' => 'usd']]],
                        ['match' => ['code' => ['query' => 'xoxo']]],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_build_a_raw_query(): void
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

    #[Test]
    public function can_build_a_must_exist_query(): void
    {
        $actual = Room::asRaw()->mustExist('currency')->toQuery();

        $expected = [
            'query' => [
                'must' => [
                    ['exists' => [
                        'field' => 'currency',
                    ]],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_build_a_should_exist_query(): void
    {
        $actual = Room::asRaw()->shouldExist('currency')->toQuery();

        $expected = [
            'query' => [
                'should' => [
                    ['exists' => [
                        'field' => 'currency',
                    ]],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_build_a_match_query(): void
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

    #[Test]
    public function can_set_values_for_a_query_with_values(): void
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

    #[Test]
    public function can_build_a_fuzzy_query(): void
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

    #[Test]
    public function it_promotes_a_non_bool_query_into_bool_when_filters_are_present(): void
    {
        $actual = Room::asRaw()
            ->raw(['match' => ['description' => 'foo']])
            ->filterByRange('price', 10, 'gte')
            ->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['match' => ['description' => 'foo']],
                    ],
                    'filter' => [
                        ['range' => ['price' => ['gte' => 10]]],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_nests_multi_match_as_a_bool_must_clause(): void
    {
        $actual = Room::query()
            ->multiMatch(['title', 'body'], 'foo bar')
            ->toQuery();

        $expected = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['multi_match' => [
                            'query' => 'foo bar',
                            'fields' => ['title', 'body'],
                        ]],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
