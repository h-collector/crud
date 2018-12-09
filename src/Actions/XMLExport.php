<?php

namespace HC\Crud\Actions;

use HC\Crud\Entity;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Traversable;
use XMLWriter;

/**
 *
 */
class XMLExport extends AbstractAction
{
    /**
     * @var XMLWriter
     */
    protected $writer;

    /**
     * @param XMLWriter $writer
     */
    public function __construct(XMLWriter $writer)
    {
        $this->writer = $writer;
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Entity $entity, $id)
    {
        $repo = $this->getRepository($request, $entity, $id);

        $resource = [
            'singular' => Str::singular($entity->getResource()),
            'plural'   => Str::plural($entity->getResource()),
        ];

        $format      = 'xml';
        $contentType = 'application/xml';
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        $filename = sprintf('export_%s.%s', date('Y_m_d_U'), $format);

        // create streamed response
        $response = new StreamedResponse(function () use ($repo, $resource) {
            // echo to standard output
            $out = fopen('php://output', 'r+b');

            $this->writer->startElement($resource['plural']);

            // output in chunks or better using cursor!
            foreach ($repo->yield() as $record) {
                $this->renderElement($resource['singular'], $record);

                fwrite($out, $this->writer->flush(true));
            }

            $this->writer->endElement();

            fwrite($out, $this->writer->flush(true));

            // close output stream
            fclose($out);
        });

        $response->headers->add([
            'Content-Disposition' => sprintf($disposition . '; filename="%s"', $filename),
            'Content-Type'        => $contentType,
        ]);

        return $response;
    }

    /**
     * [renderElement description].
     *
     * @param string       $name
     * @param object|array $value
     */
    public function renderElement(string $name, $value)
    {
        if ($this->hasNumericKeys($value)) {
            $this->writer->startElement(Str::plural($name));

            foreach ($value as $idx => $subValue) {
                $this->renderElement(
                    $this->guessSingularName($name, $subValue),
                    $subValue
                );
            }

            $this->writer->endElement();

            return;
        }

        if ($this->isIterable($value)) {
            $this->writer->startElement($name);

            foreach (Collection::make($value) as $key => $prop) {
                $this->renderElement($key, $prop);
            }

            $this->writer->endElement();

            return;
        }

        $this->writer->writeElement((string) $name, $value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function isIterable($value)
    {
        if (is_array($value) || $value instanceof Arrayable || $value instanceof Traversable) {
            return true;
        }

        if (is_object($value)) {
            return ! method_exists($value, '__toString');
        }

        return false;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function hasNumericKeys($value)
    {
        if (is_array($value)) {
            return is_int(key($value));
        }

        if ($value instanceof Collection) {
            return is_int($value->keys()[0] ?? null);
        }

        return false;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return string
     */
    public function guessSingularName($key, $value)
    {
        if (is_object($value)) {
            return Str::kebab(class_basename($value));
        }

        return $key;
    }
}
