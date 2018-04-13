<?php declare(strict_types=1);
namespace Onion\Framework\Http\Factory;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\parse_header;
use Psr\Http\Message\ServerRequestInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

final class ServerRequestFactory implements FactoryInterface
{
    /**
     * Method that handles the construction of the object
     *
     * @param ContainerInterface $container DI Container
     *
     * @return ServerRequestInterface
     */
    public function build(ContainerInterface $container): ServerRequestInterface
    {
        $request = new ServerRequest(
            filter_input(INPUT_SERVER, 'REQUEST_METHOD'),
            new Uri(filter_input(INPUT_SERVER, 'REQUEST_URI')),
            $this->getHeaders(filter_input_array(INPUT_SERVER)),
            stream_for(fopen('php://input', 'rb'))
        );

        return $request->withUploadedFiles(
            $this->getFiles($_FILES)
        );
    }

    private function getHeaders(array $input)
    {
        $headers = [];
        foreach ($input as $key => $value) {
            if (strpos($key, 'REDIRECT_') === 0) {
                $key = substr($key, 9);

                if (array_key_exists($key, $headers)) {
                    continue;
                }
            }

            if ($value && strpos($key, 'HTTP_') === 0) {
                $name = strtr(strtolower(substr($key, 5)), '_', '-');
                $headers[$name] = $value;
                continue;
            }

            if ($value && strpos($key, 'CONTENT_') === 0) {
                $name = 'content-' . strtolower(substr($key, 8));
                $headers[$name] = $value;
                continue;
            }
        }

        return $headers;
    }

    private function getFiles(array $files)
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                    foreach (array_keys($value['tmp_name']) as $key) {
                        $spec = [
                            'tmp_name' => $value['tmp_name'][$key],
                            'size'     => $value['size'][$key],
                            'error'    => $value['error'][$key],
                            'name'     => $value['name'][$key],
                            'type'     => $value['type'][$key],
                        ];
                        $normalized[$key] = $this->createFile($spec);
                    }
                }

                $normalized[$key] = $this->createFile($value);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->getFiles($value);
                continue;
            }

            throw new InvalidArgumentException('Invalid value in files specification');
        }

        return $normalized;
    }

    private function createFile($value)
    {
        return new UploadedFile(
            $value['tmp_name'],
            $value['size'],
            $value['error'],
            $value['name'],
            $value['type']
        );
    }
}
