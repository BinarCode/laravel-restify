<?php

namespace Binaryk\LaravelRestify\Tests\MCP\Files;

use Binaryk\LaravelRestify\MCP\Files\RemoteFileFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RemoteFileFetcherTest extends TestCase
{
    private const PUBLIC_IPV4 = '93.184.216.34';

    private const PUBLIC_IPV6 = '2606:2800:220:1:248:1893:25c8:1946';

    public function test_it_resolves_the_host_once_and_downloads_from_the_addresses_it_validated(): void
    {
        // A host that answers with a public address first and with a loopback address ever after.
        $resolver = new FakeHostResolver([self::PUBLIC_IPV4], ['127.0.0.1']);
        $transport = new FakeRemoteFileTransport('contents');

        $contents = (new RemoteFileFetcher($resolver, $transport))
            ->fetch('https://files.example.com/images/photo.png?size=large#preview', 2048, 7);

        $this->assertSame('contents', $contents);
        $this->assertSame(['files.example.com'], $resolver->lookups);
        $this->assertCount(1, $transport->downloads);

        ['file' => $file, 'maxBytes' => $maxBytes, 'timeout' => $timeout] = $transport->downloads[0];

        $this->assertSame([self::PUBLIC_IPV4], $file->addresses);
        $this->assertSame('https://93.184.216.34:443/images/photo.png?size=large', $file->urlFor($file->addresses[0]));
        $this->assertSame('files.example.com', $file->host);
        $this->assertSame('files.example.com', $file->hostHeader());
        $this->assertFalse($file->hostIsAddress());
        $this->assertNull($file->authorization);
        $this->assertSame(2048, $maxBytes);
        $this->assertSame(7, $timeout);
    }

    /**
     * @return array<string, array{list<string>}>
     */
    public static function answersWithAnAddressThatMustNotBeReached(): array
    {
        return [
            'only a loopback address' => [['127.0.0.1']],
            'only a private address' => [['10.0.0.5']],
            'only the cloud metadata address' => [['169.254.169.254']],
            'only a carrier-grade NAT address' => [['100.64.0.1']],
            'a private address after a public one' => [[self::PUBLIC_IPV4, '192.168.1.10']],
            'a private address before a public one' => [['192.168.1.10', self::PUBLIC_IPV4]],
            'an IPv6 loopback address next to a public IPv4 one' => [[self::PUBLIC_IPV4, '::1']],
            'a unique local address next to public ones' => [[self::PUBLIC_IPV4, self::PUBLIC_IPV6, 'fd00::1']],
            'an IPv4-mapped loopback address' => [[self::PUBLIC_IPV4, '::ffff:127.0.0.1']],
            'a NAT64 address of a private one' => [[self::PUBLIC_IPV4, '64:ff9b::a00:1']],
            'something that is not an address' => [[self::PUBLIC_IPV4, 'files.example.com']],
            'no address' => [[]],
        ];
    }

    /**
     * @param  list<string>  $answer
     */
    #[DataProvider('answersWithAnAddressThatMustNotBeReached')]
    public function test_a_host_is_refused_unless_every_address_it_resolves_to_is_public(array $answer): void
    {
        $resolver = new FakeHostResolver($answer);
        $transport = new FakeRemoteFileTransport;
        $fetcher = new RemoteFileFetcher($resolver, $transport);

        $this->assertNull($fetcher->pin('https://files.example.com/photo.png'));
        $this->assertNull($fetcher->fetch('https://files.example.com/photo.png', 1024, 5));
        $this->assertSame([], $transport->downloads);
    }

    public function test_it_pins_the_first_address_of_each_family_with_ipv4_first(): void
    {
        $resolver = new FakeHostResolver(['2606:4700:4700::1111', '1.1.1.1', '1.0.0.1', '2606:4700:4700::1001']);

        $file = (new RemoteFileFetcher($resolver, new FakeRemoteFileTransport))->pin('http://files.example.com/photo.png');

        $this->assertNotNull($file);
        $this->assertSame(['1.1.1.1', '2606:4700:4700::1111'], $file->addresses);
        $this->assertSame('http://1.1.1.1:80/photo.png', $file->urlFor('1.1.1.1'));
        $this->assertSame('http://[2606:4700:4700::1111]:80/photo.png', $file->urlFor('2606:4700:4700::1111'));
    }

    public function test_it_pins_an_ipv6_only_host(): void
    {
        $resolver = new FakeHostResolver(['2606:4700:4700:0:0:0:0:1111']);

        $file = (new RemoteFileFetcher($resolver, new FakeRemoteFileTransport))->pin('https://files.example.com/');

        $this->assertNotNull($file);
        $this->assertSame(['2606:4700:4700::1111'], $file->addresses);
    }

    public function test_pinning_does_not_download(): void
    {
        $transport = new FakeRemoteFileTransport;

        $file = (new RemoteFileFetcher(new FakeHostResolver([self::PUBLIC_IPV4]), $transport))
            ->pin('https://files.example.com/photo.png');

        $this->assertNotNull($file);
        $this->assertSame([], $transport->downloads);
    }

    public function test_a_public_address_as_the_host_is_pinned_to_itself_without_a_lookup(): void
    {
        $resolver = new FakeHostResolver(['127.0.0.1']);
        $fetcher = new RemoteFileFetcher($resolver, new FakeRemoteFileTransport);

        $ipv4 = $fetcher->pin('http://93.184.216.34:8080/photo.png');
        $ipv6 = $fetcher->pin('https://[2606:4700:4700:0::1111]/photo.png');

        $this->assertSame([], $resolver->lookups);

        $this->assertNotNull($ipv4);
        $this->assertSame(['93.184.216.34'], $ipv4->addresses);
        $this->assertSame('93.184.216.34:8080', $ipv4->hostHeader());
        $this->assertSame('http://93.184.216.34:8080/photo.png', $ipv4->urlFor('93.184.216.34'));
        $this->assertTrue($ipv4->hostIsAddress());

        $this->assertNotNull($ipv6);
        $this->assertSame(['2606:4700:4700::1111'], $ipv6->addresses);
        $this->assertSame('[2606:4700:4700::1111]', $ipv6->hostHeader());
        $this->assertSame('https://[2606:4700:4700::1111]:443/photo.png', $ipv6->urlFor('2606:4700:4700::1111'));
        $this->assertTrue($ipv6->hostIsAddress());
    }

    /**
     * URLs refused for what they say, before any lookup. The resolver would answer with a public address.
     *
     * @return array<string, array{string}>
     */
    public static function urlsRefusedWithoutALookup(): array
    {
        return [
            // Not http or https, or not a URL with a host.
            'ftp' => ['ftp://files.example.com/photo.png'],
            'file' => ['file:///etc/hosts'],
            'gopher' => ['gopher://files.example.com/'],
            'data' => ['data:text/plain;base64,AAAA'],
            'no scheme' => ['files.example.com/photo.png'],
            'protocol relative' => ['//files.example.com/photo.png'],
            'local path' => ['/etc/hosts'],
            'no host' => ['http:///photo.png'],
            'empty' => [''],

            // Addresses that must not be reached.
            'loopback' => ['http://127.0.0.1/'],
            'loopback with a port' => ['http://127.0.0.1:8080/'],
            'unspecified' => ['http://0.0.0.0/'],
            'private' => ['http://10.0.0.5/'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data'],
            'carrier-grade NAT' => ['http://100.64.0.1/'],
            'benchmarking' => ['http://198.18.0.1/'],
            'multicast' => ['http://224.0.0.1/'],
            'broadcast' => ['http://255.255.255.255/'],
            'IPv6 loopback' => ['http://[::1]/'],
            'IPv6 unspecified' => ['http://[::]/'],
            'IPv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'IPv4-mapped loopback in hex' => ['http://[::ffff:7f00:1]/'],
            'IPv4-mapped public address' => ['http://[::ffff:8.8.8.8]/'],
            'IPv4-compatible loopback' => ['http://[::127.0.0.1]/'],
            'NAT64 of a private address' => ['http://[64:ff9b::a00:1]/'],
            '6to4 of a loopback address' => ['http://[2002:7f00:1::1]/'],
            'unique local' => ['http://[fd00::1]/'],
            'link-local' => ['http://[fe80::1]/'],
            'link-local with a zone' => ['http://[fe80::1%25eth0]/'],
            'IPv6 multicast' => ['http://[ff02::1]/'],
            'IPv6 documentation' => ['http://[2001:db8::1]/'],

            // Spellings of an IPv4 address that only some parsers read as one.
            'decimal' => ['http://2130706433/'],
            'hexadecimal' => ['http://0x7f000001/'],
            'octal' => ['http://017700000001/'],
            'short' => ['http://127.1/'],
            'hexadecimal octet' => ['http://0x7f.0.0.1/'],
            'octal octet' => ['http://0177.0.0.1/'],
            'leading zero' => ['http://010.0.0.1/'],
            'trailing dot' => ['http://127.0.0.1./'],
            'five octets' => ['http://1.1.1.1.1/'],
            'numeric last label' => ['http://files.example.123/'],
            'hexadecimal last label' => ['http://files.example.0x1f/'],
            'IPv6 without brackets' => ['http://::1/'],
            'brackets around an IPv4 address' => ['http://[127.0.0.1]/'],
            'brackets around a name' => ['http://[files.example.com]/'],

            // Host names the resolver and the TLS layer could read differently.
            'single label' => ['http://localhost/'],
            'single label intranet name' => ['http://intranet/photo.png'],
            'fully qualified with a trailing dot' => ['http://files.example.com./'],
            'empty label' => ['http://files..example.com/'],
            'underscore' => ['http://my_files.example.com/'],
            'leading hyphen' => ['http://-files.example.com/'],
            'trailing hyphen' => ['http://files-.example.com/'],
            'not ASCII' => ['http://münchen.example.com/'],
            'percent encoded' => ['http://files%2eexample.com/'],
            'backslash before an at sign' => ['http://files.example.com\\@127.0.0.1/'],
            'label longer than 63 characters' => ['http://'.str_repeat('a', 64).'.example.com/'],
            'name longer than 253 characters' => ['http://'.str_repeat(str_repeat('a', 60).'.', 5).'example.com/'],

            // Whitespace and control characters anywhere in the URL.
            'space in the path' => ['http://files.example.com/my photo.png'],
            'leading space' => [' http://files.example.com/photo.png'],
            'trailing newline' => ["http://files.example.com/photo.png\n"],
            'header injection' => ["http://files.example.com/photo.png\r\nX-Injected: 1"],
            'tab in the host' => ["http://files.exam\tple.com/"],
            'null byte' => ["http://files.example.com/photo.png\0.txt"],

            // Ports that are not ports.
            'port zero' => ['http://files.example.com:0/'],
            'port out of range' => ['http://files.example.com:70000/'],
            'port that is not a number' => ['http://files.example.com:http/'],
        ];
    }

    #[DataProvider('urlsRefusedWithoutALookup')]
    public function test_a_url_is_refused_for_what_it_says_before_any_lookup(string $url): void
    {
        $resolver = new FakeHostResolver([self::PUBLIC_IPV4]);
        $transport = new FakeRemoteFileTransport;
        $fetcher = new RemoteFileFetcher($resolver, $transport);

        $this->assertNull($fetcher->pin($url));
        $this->assertNull($fetcher->fetch($url, 1024, 5));
        $this->assertSame([], $resolver->lookups);
        $this->assertSame([], $transport->downloads);
    }

    /**
     * @return array<string, array{string, string, string, int, string, string}>
     */
    public static function acceptedUrls(): array
    {
        return [
            'https' => ['https://files.example.com/photo.png', 'https', 'files.example.com', 443, '/photo.png', 'files.example.com'],
            'http' => ['http://files.example.com/photo.png', 'http', 'files.example.com', 80, '/photo.png', 'files.example.com'],
            'upper case scheme and host' => ['HTTPS://Files.EXAMPLE.com/Photo.PNG', 'https', 'files.example.com', 443, '/Photo.PNG', 'files.example.com'],
            'no path' => ['http://files.example.com', 'http', 'files.example.com', 80, '/', 'files.example.com'],
            'query without a path' => ['http://files.example.com?id=1', 'http', 'files.example.com', 80, '/?id=1', 'files.example.com'],
            'query and fragment' => ['https://files.example.com/a/b.png?x=1&y=2#top', 'https', 'files.example.com', 443, '/a/b.png?x=1&y=2', 'files.example.com'],
            'other port' => ['https://files.example.com:8443/photo.png', 'https', 'files.example.com', 8443, '/photo.png', 'files.example.com:8443'],
            'http on the https port' => ['http://files.example.com:443/photo.png', 'http', 'files.example.com', 443, '/photo.png', 'files.example.com:443'],
            'default port spelled out' => ['https://files.example.com:443/photo.png', 'https', 'files.example.com', 443, '/photo.png', 'files.example.com'],
            'punycode' => ['https://xn--mnchen-3ya.example.com/photo.png', 'https', 'xn--mnchen-3ya.example.com', 443, '/photo.png', 'xn--mnchen-3ya.example.com'],
            'digits and hyphens' => ['https://cdn-1.files2.example.co.uk/photo.png', 'https', 'cdn-1.files2.example.co.uk', 443, '/photo.png', 'cdn-1.files2.example.co.uk'],
            'percent encoded path' => ['https://files.example.com/my%20photo.png', 'https', 'files.example.com', 443, '/my%20photo.png', 'files.example.com'],
            'parent segments stay in the path' => ['https://files.example.com/a/../photo.png', 'https', 'files.example.com', 443, '/a/../photo.png', 'files.example.com'],
        ];
    }

    #[DataProvider('acceptedUrls')]
    public function test_it_pins_a_public_url(string $url, string $scheme, string $host, int $port, string $target, string $hostHeader): void
    {
        $resolver = new FakeHostResolver([self::PUBLIC_IPV4]);

        $file = (new RemoteFileFetcher($resolver, new FakeRemoteFileTransport))->pin($url);

        $this->assertNotNull($file);
        $this->assertSame([$host], $resolver->lookups);
        $this->assertSame($scheme, $file->scheme);
        $this->assertSame($host, $file->host);
        $this->assertSame($port, $file->port);
        $this->assertSame($target, $file->target);
        $this->assertSame($hostHeader, $file->hostHeader());
        $this->assertSame([self::PUBLIC_IPV4], $file->addresses);
        $this->assertSame("{$scheme}://93.184.216.34:{$port}{$target}", $file->urlFor(self::PUBLIC_IPV4));
    }

    public function test_credentials_in_the_url_become_an_authorization_header_and_leave_the_url(): void
    {
        $fetcher = new RemoteFileFetcher(new FakeHostResolver([self::PUBLIC_IPV4]), new FakeRemoteFileTransport);

        $file = $fetcher->pin('https://reader:s%40cret@files.example.com/photo.png');

        $this->assertNotNull($file);
        $this->assertSame('Basic '.base64_encode('reader:s@cret'), $file->authorization);
        $this->assertSame('files.example.com', $file->host);
        $this->assertSame('https://93.184.216.34:443/photo.png', $file->urlFor(self::PUBLIC_IPV4));

        // The part before the at sign is a user name, not the host.
        $file = $fetcher->pin('https://files.example.com@other.example.org/photo.png');

        $this->assertNotNull($file);
        $this->assertSame('other.example.org', $file->host);
        $this->assertSame('Basic '.base64_encode('files.example.com:'), $file->authorization);
    }

    public function test_a_failed_download_is_null(): void
    {
        $fetcher = new RemoteFileFetcher(new FakeHostResolver([self::PUBLIC_IPV4]), new FakeRemoteFileTransport(null));

        $this->assertNull($fetcher->fetch('https://files.example.com/photo.png', 1024, 5));
    }
}
