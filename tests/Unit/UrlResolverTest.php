<?php
declare(strict_types=1);

namespace Mosaicora\WordPress\Tests\Unit;

use Mosaicora\WordPress\UrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlResolverTest extends TestCase
{
    protected function setUp(): void
    {
        mosaicora_test_reset();
    }

    public function testResolvesSingularCanonicalUrl(): void
    {
        self::assertSame('https://example.com/article', (new UrlResolver())->resolve());
    }

    public function testResolvesArchivePaginationUrl(): void
    {
        $GLOBALS['mosaicora_test']['conditionals']['singular'] = false;
        $GLOBALS['mosaicora_test']['conditionals']['archive'] = true;
        $GLOBALS['mosaicora_test']['paged_url'] = 'https://example.com/news/page/2/';

        self::assertTrue((new UrlResolver())->isEligible());
        self::assertSame('https://example.com/news/page/2/', (new UrlResolver())->resolve());
    }

    #[DataProvider('excludedConditionals')]
    public function testExcludesNonIndexableViews(string $conditional): void
    {
        $GLOBALS['mosaicora_test']['conditionals'][$conditional] = true;

        self::assertFalse((new UrlResolver())->isEligible());
    }

    /** @return iterable<string, array{string}> */
    public static function excludedConditionals(): iterable
    {
        yield 'admin' => ['admin'];
        yield 'feed' => ['feed'];
        yield 'preview' => ['preview'];
        yield 'search' => ['search'];
        yield '404' => ['404'];
        yield 'JSON' => ['json'];
    }
}
