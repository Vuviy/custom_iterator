<?php

namespace tests;

use App\Collection;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testToArrayReturnsOriginalItems(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function testFilterIsImmutable(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $filtered = $collection->filter(fn ($x) => $x > 2);

        $this->assertSame([1, 2, 3, 4], $collection->toArray());
        $this->assertSame([3, 4], $filtered->toArray());
    }

    public function testMapWorks(): void
    {
        $collection = new Collection([1, 2, 3]);

        $mapped = $collection->map(fn ($x) => $x * 10);

        $this->assertSame([10, 20, 30], $mapped->toArray());
    }

    public function testFilterThenMapChaining(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $result = $collection
            ->filter(fn ($x) => $x % 2 === 0)
            ->map(fn ($x) => $x * 10);

        $this->assertSame([20, 40], $result->toArray());
    }

    public function testReduceReturnsScalar(): void
    {
        $collection = new Collection([1, 2, 3]);

        $sum = $collection->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(6, $sum);
    }

    public function testWhereEqualsOperator(): void
    {
        $collection = new Collection([
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
        ]);

        $result = $collection->where('active', '=', true);

        $this->assertSame(
            [['id' => 1, 'active' => true]],
            $result->toArray()
        );
    }

    public function testWhereGreaterThanOperator(): void
    {
        $collection = new Collection([
            ['age' => 17],
            ['age' => 18],
            ['age' => 25],
        ]);

        $result = $collection->where('age', '>=', 18);

        $this->assertSame(
            [['age' => 18], ['age' => 25]],
            $result->toArray()
        );
    }

    public function testWhereSupportsNestedFields(): void
    {
        $collection = new Collection([
            ['profile' => ['age' => 20]],
            ['profile' => ['age' => 15]],
        ]);

        $result = $collection->where('profile.age', '>=', 18);

        $this->assertSame(
            [['profile' => ['age' => 20]]],
            $result->toArray()
        );
    }

    public function testCountable(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $this->assertCount(4, $collection);
    }

    public function testIteratorWorksWithForeach(): void
    {
        $collection = new Collection([1, 2, 3]);

        $result = [];
        foreach ($collection as $item) {
            $result[] = $item;
        }

        $this->assertSame([1, 2, 3], $result);
    }

    public function testArrayAccessGet(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $this->assertSame('b', $collection[1]);
    }

    public function testArrayAccessSetIsForbidden(): void
    {
        $this->expectException(LogicException::class);

        $collection = new Collection([1, 2, 3]);
        $collection[0] = 100;
    }

    public function testArrayAccessUnsetIsForbidden(): void
    {
        $this->expectException(LogicException::class);

        $collection = new Collection([1, 2, 3]);
        unset($collection[0]);
    }

    public function testJsonSerialize(): void
    {
        $collection = new Collection(['x', 'y']);

        $json = json_encode($collection);

        $this->assertSame('["x","y"]', $json);
    }

    public function testLazyEvaluationRunsCallbackOnlyOnEvaluation(): void
    {
        $calls = 0;

        $collection = new Collection([1, 2, 3]);

        $mapped = $collection->map(function ($item) use (&$calls) {
            $calls++;
            return $item * 2;
        });

        $this->assertSame(0, $calls);

        $mapped->toArray();

        $this->assertSame(3, $calls);
    }

    public function testEvaluateIsExecutedOnlyOnce(): void
    {
        $calls = 0;

        $collection = new Collection(range(1, 1000));

        $mapped = $collection->map(function ($item) use (&$calls) {
            $calls++;
            return $item * 2;
        });

        $mapped->toArray();
        $this->assertSame(1000, $calls);

        $mapped->toArray();
        $mapped->count();
        $mapped[0];

        $this->assertSame(1000, $calls);
    }

    public function testLazyEvaluationDoesNotRunBeforeAccess(): void
    {
        $calls = 0;

        $collection = new Collection([1, 2, 3]);

        $collection
            ->filter(function ($item) use (&$calls) {
                $calls++;
                return $item > 1;
            })
            ->map(function ($item) use (&$calls) {
                $calls++;
                return $item * 10;
            });

        $this->assertSame(0, $calls);
    }

    public function testChainedOperationsRunOncePerElement(): void
    {
        $filterCalls = 0;
        $mapCalls = 0;

        $collection = new Collection(range(1, 1000));

        $result = $collection
            ->filter(function ($item) use (&$filterCalls) {
                $filterCalls++;
                return $item % 2 === 0;
            })
            ->map(function ($item) use (&$mapCalls) {
                $mapCalls++;
                return $item * 2;
            })
            ->toArray();

        $this->assertSame(1000, $filterCalls);
        $this->assertSame(500, $mapCalls);
    }

    public function testLargeDatasetDoesNotBreak(): void
    {
        $collection = new Collection(range(1, 100000));

        $result = $collection
            ->filter(fn ($x) => $x % 10 === 0)
            ->map(fn ($x) => $x * 2)
            ->count();

        $this->assertSame(10000, $result);
    }
}