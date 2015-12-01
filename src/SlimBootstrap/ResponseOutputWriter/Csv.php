<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap;
use \Slim;
use SlimBootstrap\CSVEncodingException;
use SlimBootstrap\DataObject;

/**
 * This class is responsible to output the data to the client in valid Csv
 * format.
 *
 * @package SlimBootstrap\ResponseOutputWriter
 */
class Csv implements SlimBootstrap\ResponseOutputWriter,
                     SlimBootstrap\ResponseOutputWriterStreamable
{
    /**
     * The Slim request object.
     *
     * @var Slim\Http\Request
     */
    private $_request = null;

    /**
     * The Slim response object.
     *
     * @var Slim\Http\Response
     */
    private $_response = null;

    /**
     * The Slim response headers object.
     *
     * @var Slim\Http\Headers
     */
    private $_headers = null;

    /**
     * CSV Delimiter
     *
     * @var string
     */
    private $_delimiter = ',';

    /**
     * Enclose all fields or use opportunistic enclosures
     *
     * @var bool
     */
    private $_encloseAll = false;

    /**
     * CSV Enclosure
     *
     * @var string
     */
    private $_enclosure = '"';

    /**
     * CSV Linebreak
     *
     * @var string
     */
    private $_linebreak = "\r\n";

    /**
     * CSV NULL
     *
     * @var string
     */
    private $_null = 'NULL';

    /**
     * @var array
     */
    private $_multidimensionalFields = array();

    /**
     * @var string
     */
    private $_lastFieldName = '';

    /**
     * @var bool
     */
    private $_firstCall = true;

    /**
     * @param Slim\Http\Request  $request   The Slim request object.
     * @param Slim\Http\Response $response  The Slim response object.
     * @param Slim\Http\Headers  $headers   The Slim response headers object.
     * @param String             $shortName
     * @param array              $CSVConfig optional CSV Configuration
     * @codeCoverageIgnore
     */
    public function __construct(
        Slim\Http\Request $request,
        Slim\Http\Response $response,
        Slim\Http\Headers $headers,
        $shortName,
        array $CSVConfig = null
    ) {
        $this->_request  = $request;
        $this->_response = $response;
        $this->_headers  = $headers;
        if (true === \is_array($CSVConfig)) {
            if (true === \array_key_exists('delimiter', $CSVConfig)
                && false === empty($CSVConfig['delimiter'])
            ) {
                $this->_delimiter = $CSVConfig['delimiter'];
            }

            if (true === \array_key_exists('enclosure', $CSVConfig)
                && false === empty($CSVConfig['enclosure'])
            ) {
                $this->_enclosure = $CSVConfig['enclosure'];
            }

            if (true === \array_key_exists('linebreak', $CSVConfig)
                && false === empty($CSVConfig['linebreak'])
            ) {
                $this->_linebreak = $CSVConfig['linebreak'];
            }

            if (true === \array_key_exists('encloseAll', $CSVConfig)
                && false === empty($CSVConfig['encloseAll'])
            ) {
                $this->_encloseAll = $CSVConfig['encloseAll'];
            }

            if (true === \array_key_exists('null', $CSVConfig)
                && false === empty($CSVConfig['null'])
            ) {
                $this->_null = $CSVConfig['null'];
            }
        }
    }

    /**
     * This function outputs the given $data as valid CSV to the client
     * and sets the HTTP Response Code to the given $statusCode.
     *
     * @param SlimBootstrap\DataObject[] $data The data to output to
     *                                                   the client
     * @param int $statusCode The status code to set
     *                                                   in the response
     * @throws CSVEncodingException
     */
    public function write($data, $statusCode = 200)
    {
        if ($data instanceof DataObject) {
            $data = array($data);

        } else if (false === \is_array($data)) {
            throw new CSVEncodingException(
                'Expected array of DataObjects or one DataObject, but '
                . \gettype($data)
                . ' given.'
            );
        }

        $body = $this->_csvEncode(
            $data,
            $this->_encloseAll
        );

        $this->_headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $this->_response->setStatus($statusCode);
        $this->_response->setBody($body);
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode = 200)
    {
        \http_response_code($statusCode);
    }

    /**
     * @param DataObject $entry
     */
    public function writeToStream(SlimBootstrap\DataObject $entry)
    {
        if (true === $this->_firstCall) {
            \header('Content-Type: text/csv; charset=UTF-8');

            echo $this->_determineHeadline(array($entry));

            $this->_firstCall = false;
        }

        echo $this->_buildCsvLineFromDataSet(
            $entry->getData(),
            $this->_multidimensionalFields,
            $this->_lastFieldName,
            $this->_encloseAll
        ) . $this->_linebreak;

        \flush();
        \ob_flush();
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function _determineHeadline(array $data)
    {
        $this->_multidimensionalFields = array();
        $this->_lastFieldName          = '';
        $headline                      = '';

        // evaluate header, last field name, and multidimensial keys
        /** @var SlimBootstrap\DataObject $first */
        foreach ($data as $first) {
            $firstEntry = $first->getData();

            // remove multidimensional keys, because they can't be displayed
            // in a reasonable csv
            foreach ($firstEntry as $fieldName => $fieldData) {
                if (true === \is_array($fieldData)) {
                    $this->_multidimensionalFields[$fieldName] = true;
                    unset($firstEntry[$fieldName]);
                    continue;
                }

                if ('' === $headline) {
                    $headline .= $fieldName;
                } else {
                    $headline .= $this->_delimiter . $fieldName;
                }
            }

            // evaluate last field name
            $this->_lastFieldName = key(array_slice($firstEntry, -1, 1, true));
            break;
        }

        return $headline . $this->_linebreak;
    }


    /**
     * @param   SlimBootstrap\DataObject[]  $data       array of DataObjects
     * @param   bool                        $encloseAll Force enclosing every
     *                                                  field (false)
     *
     * @return  string              Returns the CSV.
     *
     */
    protected function _csvEncode(array $data, $encloseAll = false)
    {
        $csvOutput = $this->_determineHeadline($data);

        // build csv line
        $lastEntry = end($data);
        foreach ($data as $key => $entry) {
            $csvOutput .= $this->_buildCsvLineFromDataSet(
                $entry->getData(),
                $this->_multidimensionalFields,
                $this->_lastFieldName,
                $encloseAll
            );

            if ($entry !== $lastEntry) {
                $csvOutput .= $this->_linebreak;
            }
            // unset entry to optimize memory usage
            unset($data[$key]);
        }

        return $csvOutput;
    }

    /**
     * @param  array  $fields Structured 2+-dimensional-data array
     * @param  array  $multidimensionalFields - array of field names,
     *                                         which will not be displayed
     *                                         at csv output, because they
     *                                         can not be displayed reasonable
     * @param  string $lastFieldName - name of the last data field,
     *                                 it's used to  don't set the delimiter
     *                                 after last element
     * @param  bool   $encloseAll Force enclosing every field (false)
     *
     * @return  string  Returns the line for $fields.
     *
     * Adapted from:
     * @see http://php.net/manual/en/function.fputcsv.php#87120
     */
    private function _buildCsvLineFromDataSet(
        array $fields,
        array $multidimensionalFields,
        $lastFieldName,
        $encloseAll = false
    ) {
        $delimiterEscaped = \preg_quote($this->_delimiter, '/');
        $enclosureEscaped = \preg_quote($this->_enclosure, '/');

        $output = '';
        foreach ($fields as $fileName => $field) {
            // skip multidimensional fields, because they can't be displayed
            // in a reasonable csv
            if (true === array_key_exists($fileName, $multidimensionalFields)) {
                continue;
            }

            $isLast = false;
            if ($lastFieldName === $fileName) {
                $isLast = true;
            }


            if ($field === null) {
                $output .= $this->_null;
                if (false === $isLast) {
                    $output .= $this->_delimiter;
                }
                continue;
            }

            if ($encloseAll
                || \preg_match(
                    "/(\\s|"
                    . $delimiterEscaped
                    . '|'
                    . $enclosureEscaped
                    . ')/',
                    $field
                )
            ) {
                $output .= $this->_enclosure
                            . \str_replace(
                                $this->_enclosure,
                                $this->_enclosure . $this->_enclosure,
                                $field
                            )
                            . $this->_enclosure;
            } else {
                $output .= $field;
            }

            if (false === $isLast) {
                $output .= $this->_delimiter;
            }
        }

        return $output;
    }
}
