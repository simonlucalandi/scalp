<?php

declare(strict_types=1);

namespace Scalp\Tests\Utils;

use Scalp\Utils\Failure;
use function Scalp\Utils\Failure;
use PHPUnit\Framework\TestCase;
use function Scalp\Utils\Success;
use function Scalp\Utils\TryCatch;
use Scalp\Utils\TryCatch;

class FailureTest extends TestCase
{
    /** @var Failure */
    private $failure;

    /** @test */
    public function it_is_created_by_try_catch_when_delayed_call_throws_exception(): void
    {
        $this->assertInstanceOf(Failure::class, TryCatch(function () { return 5 / 0; }));
    }

    /** @test */
    public function it_can_be_casted_to_string(): void
    {
        $this->assertEquals(
            'Failure[Scalp\Tests\Utils\ExampleException]("Error: Example Exception.")',
            (string) Failure(new ExampleException('Error: Example Exception.'))
        );
    }

    /** @test */
    public function it_is_failure(): void
    {
        $this->assertTrue($this->failure->isFailure());
    }

    /** @test */
    public function it_is_not_success(): void
    {
        $this->assertFalse($this->failure->isSuccess());
    }

    /** @test */
    public function get_or_else_will_return_default(): void
    {
        $this->assertEquals('default', $this->failure->getOrElse('default'));
    }

    /** @test */
    public function flat_map_will_return_this(): void
    {
        $function = function (int $x): TryCatch { return Success($x * $x); };

        $this->assertEquals($this->failure, $this->failure->flatMap($function));
    }

    /** @test */
    public function map_will_return_this(): void
    {
        $function = function (int $x): int { return $x * $x; };

        $this->assertEquals($this->failure, $this->failure->map($function));
    }

    protected function setUp(): void
    {
        $this->failure = Failure(new \DomainException());

        parent::setUp();
    }
}