<?php

declare(strict_types=1);

namespace Scalp\Tests\Utils;

use function Scalp\None;
use Scalp\Tests\RememberCall;
use Scalp\Utils\Failure;
use function Scalp\Utils\Failure;
use PHPUnit\Framework\TestCase;
use function Scalp\Utils\Success;
use function Scalp\Utils\TryCatch;
use Scalp\Utils\TryCatch;

class FailureTest extends TestCase
{
    private const DOMAIN_EXCEPTION_MESSAGE = 'Domain exception error message';

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
    public function or_else_will_return_default(): void
    {
        $this->assertEquals(Success('default'), $this->failure->orElse(Success('default')));
    }

    /** @test */
    public function or_else_require_default_to_try_catch(): void
    {
        $this->expectException(\TypeError::class);

        $this->failure->orElse('default');
    }

    /** @test */
    public function get_throws_error_from_this(): void
    {
        try {
            $this->failure->get();
        } catch (\DomainException $error) {
            $this->assertEquals(self::DOMAIN_EXCEPTION_MESSAGE, $error->getMessage());
        }
    }

    /** @test */
    public function foreach_does_nothing(): void
    {
        $function = new RememberCall();

        $result = $this->failure->foreach($function);

        $this->assertNull($result);
        $this->assertNull($function->calledWith());
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

    /** @test */
    public function filter_will_return_this(): void
    {
        $predicate = function (): bool {
            return true;
        };

        $this->assertEquals($this->failure, $this->failure->filter($predicate));
    }

    /** @test */
    public function recover_with_will_call_function_with_value_from_this_and_return_result(): void
    {
        $pf = function (\Throwable $error): TryCatch {
            return Success($error->getMessage());
        };

        $this->assertEquals(Success(self::DOMAIN_EXCEPTION_MESSAGE), $this->failure->recoverWith($pf));
    }

    /** @test */
    public function recover_with_function_must_return_try_catch(): void
    {
        $this->expectException(\TypeError::class);

        $pf = function (\Throwable $error): string {
            return $error->getMessage();
        };

        $this->failure->recoverWith($pf);
    }

    /** @test */
    public function recover_with_will_return_failure_with_new_error_when_recover_function_throws_an_error(): void
    {
        $pf = function (\Throwable $error): TryCatch {
            throw new \RuntimeException('Error from recover function');
        };

        $result = $this->failure->recoverWith($pf);

        $this->assertInstanceOf(Failure::class, $result);
        $this->assertEquals('Failure[RuntimeException]("Error from recover function")', (string) $result);
    }

    /** @test */
    public function recover_will_call_function_with_value_from_this_and_return_in_success(): void
    {
        $pf = function (\Throwable $error): string {
            return $error->getMessage();
        };

        $this->assertEquals(Success(self::DOMAIN_EXCEPTION_MESSAGE), $this->failure->recover($pf));
    }

    /** @test */
    public function recover_will_return_failure_with_new_error_when_recover_function_throws_an_error(): void
    {
        $pf = function (\Throwable $error): string {
            throw new \RuntimeException('Error from recover function');
        };

        $result = $this->failure->recover($pf);

        $this->assertInstanceOf(Failure::class, $result);
        $this->assertEquals('Failure[RuntimeException]("Error from recover function")', (string) $result);
    }

    /** @test */
    public function recover_will_call_function_returning_success_and_return_nested_success_type(): void
    {
        $pf = function (\Throwable $error): TryCatch {
            return Success('Error from recover function');
        };

        $this->assertEquals(Success(Success('Error from recover function')), $this->failure->recover($pf));
    }

    /** @test */
    public function to_option_returns_none(): void
    {
        $this->assertEquals(None(), Failure(new \RuntimeException())->toOption());
    }

    /** @test */
    public function it_cannot_contain_failure(): void
    {
        $this->expectException(\TypeError::class);

        Failure(Failure(new \RuntimeException()));
    }

    /** @test */
    public function it_cannot_contain_success(): void
    {
        $this->expectException(\TypeError::class);

        Failure(Success(42));
    }

    /** @test */
    public function flatten_will_return_this(): void
    {
        $this->assertEquals($this->failure, $this->failure->flatten());
    }

    /** @test */
    public function failed_will_return_value_from_this_wrapped_in_success(): void
    {
        $this->assertEquals(Success(new \DomainException(self::DOMAIN_EXCEPTION_MESSAGE)), $this->failure->failed());
    }

    /** @test */
    public function transform_will_apply_failure_function_to_value_from_this(): void
    {
        $s = function (): TryCatch {
            throw new \RuntimeException('Success function should never be called');
        };
        $f = function (\Throwable $error): TryCatch {
            return Success($error->getMessage());
        };

        $this->assertEquals(Success(self::DOMAIN_EXCEPTION_MESSAGE), $this->failure->transform($s, $f));
    }

    /** @test */
    public function transform_will_return_failure_with_new_error_when_failure_function_throws_an_error(): void
    {
        $s = function (): TryCatch {
            throw new \RuntimeException('Success function should never be called');
        };
        $f = function (\Throwable $error): TryCatch {
            throw new \RuntimeException('Error from failure function');
        };

        $this->assertEquals(Failure(new \RuntimeException('Error from failure function')), $this->failure->transform($s, $f));
    }

    /** @test */
    public function transform_requires_failure_function_to_return_try_catch(): void
    {
        $this->expectException(\TypeError::class);

        $s = function (): TryCatch {
            throw new \RuntimeException('Success function should never be called');
        };
        $f = function (\Throwable $error): string {
            return $error->getMessage();
        };

        $this->failure->transform($s, $f);
    }

    /** @test */
    public function fold_will_call_first_function_with_value_from_this(): void
    {
        $fa = function (\Throwable $error): string {
            return $error->getMessage();
        };

        $fb = function (): void {
            throw new \RuntimeException('Second function should never be called');
        };

        $this->assertEquals(self::DOMAIN_EXCEPTION_MESSAGE, $this->failure->fold($fa, $fb));
    }

    protected function setUp(): void
    {
        $this->failure = Failure(new \DomainException(self::DOMAIN_EXCEPTION_MESSAGE));

        parent::setUp();
    }
}
