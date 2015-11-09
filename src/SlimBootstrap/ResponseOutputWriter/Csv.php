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
class Csv implements SlimBootstrap\ResponseOutputWriter
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
     * Keyspace delimiter
     *      Used if a multidimensional array is used to map values to
     *          "<parentKey><keyspaceDelimiter><childKey>"
     *      E.g.
     *          array("user" => array("name" => "foobar"))
     *      with
     *          $_keyspaceDelimiter = '_'
     *      would become
     *          "user_name"
     *
     *
     * @var string
     */
    private $_keyspaceDelimiter  = '_';

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

            if (true === \array_key_exists('keyspaceDelimiter', $CSVConfig)
                && false === empty($CSVConfig['keyspaceDelimiter'])
            ) {
                $this->_keyspaceDelimiter = $CSVConfig['keyspaceDelimiter'];
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
        $result = array();

        if (true === \is_array($data)) {
            $data = $this->_normalizeAll($data);
            $identifiers = null;
            foreach ($data as $entry) {
                $this->_buildStructure(
                    $entry,
                    $entry->getIdentifiers(),
                    0,
                    $result
                );
            }
        } else if ($data instanceof DataObject) {
            $this->_buildStructure(
                $this->_normalizeOne($data),
                $data->getIdentifiers(),
                0,
                $result
            );
        } else {
            throw new CSVEncodingException(
                "Expected DataObject, " . \gettype($data) . " given."
            );
        }

        $body = $this->_csvEncode($result, $this->_encloseAll);

        $this->_headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $this->_response->setStatus($statusCode);
        $this->_response->setBody($body);
    }

    /**
     * This function adds keys to the DataObject that are necessary
     * to form uniform data. Nonexistent keys will be filled with null.
     *
     * @param SlimBootstrap\DataObject  $data   DataObject to modify
     * @param array|null                $keys   Array of keys that need
     *                                          to exist.
     * @return SlimBootstrap\DataObject         DataObject w/ filled Keys
     */
    protected function _normalizeOne(DataObject $data, $keys = null)
    {
        if (null === $keys) {
            $keys = \array_keys($data->getData());
        }

        $keys = \array_fill_keys($keys, null);

        $countWantedKeys = \count(
            \array_intersect_key(
                $keys,
                $data->getData()
            )
        );

        if ($countWantedKeys !== \count($keys)) {
            return new DataObject(
                $data->getIdentifiers(),
                \array_merge($keys, $data->getData()),
                $data->getLinks()
            );
        } else {
            return $data;
        }
    }

    /**
     * This function ensures that in all given DataObjects the
     * same keys exist. Nonexistent keys in a DataObject are
     * filled with null.
     *
     * @param SlimBootstrap\DataObject[]    $data   DataObjects to normalize
     *
     * @return SlimBootstrap\DataObject[]   Normalized DataObjects
     *
     * @throws SlimBootstrap\CSVEncodingException
     */
    protected function _normalizeAll($data)
    {
        $keys           = array();
        $identifierKeys = null;
        $newData        = array();
        $returnData     = array();

        foreach ($data as $entry) {
            if (null === $identifierKeys) {
                $identifierKeys = \array_keys($entry->getIdentifiers());
            } else {
                if (
                    $identifierKeys !=
                    \array_keys($entry->getIdentifiers())
                ) {
                    throw new CSVEncodingException("Different identifiers!");
                }
            }
            $newData[] = new DataObject(
                $entry->getIdentifiers(),
                $this->_flatten($entry->getData()),
                $entry->getLinks()
            );
            $keys = \array_merge($keys, \array_keys($entry->getData()));
        }

        foreach ($newData as $key => $value) {
            $returnData[] = $this->_normalizeOne($value, $keys);
        }

        return $returnData;
    }

    /**
     * Creates a structured array for each given payload.
     *
     * @param SlimBootstrap\DataObject $data     The payload of a DataObject
     * @param array $identifiers The identifiers to build the array structure
     * @param int   $index       The index of the current element in the
     *                              identifiers array
     * @param array $result      Reference of the result array to fill
     */
    protected function _buildStructure(
        DataObject $data,
        array $identifiers,
        $index,
        array &$result
    ) {
        $newIdentifiers = array();
        foreach ($identifiers as $key => $value) {
            $newIdentifiers['identifier' . $this->_keyspaceDelimiter . $key]
                = $value;
        }
        $newIdentifiers = $this->_flatten($newIdentifiers);

        $tmp = \array_merge($newIdentifiers, $data->getData());
        if (null !== $tmp) {
            $result[] = $tmp;
        }
    }

    /**
     * This function flattens an array or DataObject.
     *
     * @param   array|SlimBootstrap\DataObject  $origin
     *                                              Array/DataObject to flatten
     * @param   string                          $namespace
     *                                              or null (used for recursion)
     * @return  array   Flattened payload
     */
    private function _flatten($origin, $namespace = null)
    {
        $target = array();

        foreach ($origin as $key => $value) {
            if (null === $namespace) {
                $keyspace = $key;
            } else {
                $keyspace = $namespace . $this->_keyspaceDelimiter . $key;
            }

            if ($value instanceof DataObject) {
                $value = $this->_normalizeOne($value);
                $target = \array_merge(
                    $target,
                    $this->_flatten($value->getIdentifiers(), $keyspace),
                    $this->_flatten($value->getData(), $keyspace)
                );
            } else if (true === \is_array($value)) {
                $target = \array_merge(
                    $target,
                    $this->_flatten($value, $keyspace)
                );
            } else {
                $target[$keyspace] = $value;
            }
        }

        return $target;
    }

    /**
     * @param   array   $data       Structured & flattened payload as 2D-array
     *                                  array($payload1, $payload2, ...);
     * @param   bool    $encloseAll Force enclosing every field (false)
     *
     * @return  string              Returns the CSV.
     *
     * @throws \SlimBootstrap\CSVEncodingException
     */
    protected function _csvEncode($data, $encloseAll = false)
    {
        if (false === \is_array($data)) {
            throw new CSVEncodingException(
                "Expected array, " . \gettype($data) . " given."
            );
        }

        $returnCsv = array();

        /*
         * Get first array entry to fill headers. We do not know if the first
         * $payload is an array as expected or another type.
         */
        foreach ($data as $first) {
            if (false === \is_array($first)) {
                continue;
            }
            $returnCsv[] = \implode(
                $this->_delimiter,
                \array_keys($first)
            );

            break;
        }

        foreach ($data as $entry) {
            if (false === \is_array($entry)) {
                continue;
            }
            $returnCsv[] = $this->_buildCsvLineFromDataSet($entry, $encloseAll);
        }

        if (0 == \count($returnCsv)) {
            throw new CSVEncodingException("No content");
        }

        return \implode($this->_linebreak, $returnCsv);
    }

    /**
     * @param   array $fields Structured 2+-dimensional-data array
     * @param   bool $encloseAll Force enclosing every field (false)
     * @return  string  Returns the line for $fields.
     *
     * @throws SlimBootstrap\CSVEncodingException
     * Adapted from:
     * @see http://php.net/manual/en/function.fputcsv.php#87120
     */
    private function _buildCsvLineFromDataSet(
        array $fields,
        $encloseAll = false
    ) {
        $delimiterEscaped = \preg_quote($this->_delimiter, '/');
        $enclosureEscaped = \preg_quote($this->_enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null) {
                $output[] = $this->_null;
                continue;
            } else if (true === \is_array($field)) {
                throw new CSVEncodingException("Malformed payload!");
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
                $output[] = $this->_enclosure
                            . \str_replace(
                                $this->_enclosure,
                                $this->_enclosure . $this->_enclosure,
                                $field
                            )
                            . $this->_enclosure;
            } else {
                $output[] = $field;
            }
        }

        return \implode($this->_delimiter, $output);
    }
}
