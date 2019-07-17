<?php

namespace HC\Crud\Actions;

use HC\Crud\Entity;
use HC\Crud\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 *
 */
class CsvExport extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Entity $entity, $id)
    {
        $repo = $this->getRepository($request, $entity, $id);

        $fields = $this->parseFields(
            $request->input('fields')
        );

        if ($limit = $request->input('limit')) {
            if ($repo instanceof EloquentRepository) {
                $repo->getQuery()->take($limit);
            }
        }

        $format      = 'csv';
        $contentType = 'text/csv';
        $disposition = $request->has('download') ? 'attachment' : 'inline';
        $filename    = sprintf('export_%s.%s', date('Y_m_d_U'), $format);

        // create streamed response
        $response = new StreamedResponse(function () use ($repo, $fields) {
            // echo to standard output
            $out = fopen('php://output', 'r+b');

            fputcsv($out, $fields);

            // output in chunks or better using cursor!
            foreach ($repo->yield() as $record) {
                if ($fields) {
                    foreach ($fields as $key => $label) {
                        $columns[$key] = data_get($record, $key);
                    }
                } else {
                    $columns = $record->toArray();
                }

                fputcsv($out, $columns);
            }

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
     * @param string $str
     *
     * @return array
     */
    public function parseFields($str)
    {
        $fields = [];

        foreach ($this->split($str) as $field) {
            [$key, $label] = explode(' as ', $field, 2) + [1 => $field];

            $fields[trim($key)] = $label;
        }

        return $fields;
    }
}
