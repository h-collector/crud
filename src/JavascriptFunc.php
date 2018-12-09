<?php

namespace HC\Crud;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;
use SplFileObject;
use Symfony\Component\Finder\Finder;

/**
 */
class JavascriptFunc implements Arrayable, JsonSerializable
{
    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var array
     */
    protected static $paths =[];

    /**
     * @param string $args if body empty then full function notation
     * @param string $body
     *
     * @todo args also as array?
     */
    public function __construct(string $args, string $body = '')
    {
        $args = $this->joinLines($args);

        if (preg_match('/^function([\s\w]*)\(([^)]*)\)\s*{(.*?)}$/i', $args, $matches)) {
            $name = $matches[1];
            $args = $matches[2];
            $body = $matches[3];
        }

        $this->args = $this->explode(',', $args);
        $this->body = $this->joinLines($body);

        if (empty($this->body)) {
            throw new InvalidArgumentException('Given Function don\'t have body');
        }
    }

    /**
     * @param string $name alias name for file
     * @param string $path Path to file
     */
    public static function registerFile(string $name, string $path)
    {
        static::$paths[$name] = $path;
    }

    /**
     * @param string     $dir
     * @param string|int $level The depth level expression
     */
    public static function registerFilesFromDir(string $dir, $level = 0)
    {
        $ext    = '.js';
        $finder = Finder::create()->in($dir)->files()->name('*' . $ext);

        if ($level) {
            $finder->depth($level);
        }

        foreach ($finder->getIterator() as $file) {
            // get relative path in dot.notation
            if ($path = $file->getRelativePath()) {
                $path = str_replace('/', '.', $path) . '.';
            }
            static::registerFile($path . $file->getBasename($ext), $file->getPathname());
        }
    }

    /**
     * @param string $path or registered name
     *
     * @return $this
     */
    public static function file(string $path)
    {
        $path = static::$paths[$path] ?? $path;

        $file = new SplFileObject($path, 'rb');

        if ('js' !== $file->getExtension()) {
            throw new InvalidArgumentException('Not a javascript file');
        }

        return new static(
            $file->fread($file->getSize())
        );
    }

    /**
     * @param string $delimeter
     * @param string $string
     *
     * @return array
     */
    protected function explode(string $delimeter, string $string)
    {
        return array_filter(array_map('trim', explode($delimeter, $string)), function ($line) {
            return '' !== $line;
        });
    }

    /**
     * @param string $body
     *
     * @return string
     */
    protected function joinLines(string $body)
    {
        return implode(' ', $this->explode(PHP_EOL, $body));
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'function' => [
                'args' => $this->args,
                'body' => $this->body,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'function(' . implode(',', $this->args) . '){' . $this->body . '}';
    }
}
