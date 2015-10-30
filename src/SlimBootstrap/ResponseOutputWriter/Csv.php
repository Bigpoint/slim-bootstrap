<?php
namespace SlimBootstrap\ResponseOutputWriter;

use \SlimBootstrap;
use \Slim;

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
     *      would become
     *          "user_name"
     *
     *
     * @var string
     */
    private $_keyspaceDelimiter  = '_';

    /**
     * @param Slim\Http\Request  $request  The Slim request object.
     * @param Slim\Http\Response $response The Slim response object.
     * @param Slim\Http\Headers  $headers  The Slim response headers object.
     * @param String             $shortName
     */
    public function __construct(
        Slim\Http\Request $request,
        Slim\Http\Response $response,
        Slim\Http\Headers $headers,
        $shortName
    ) {
        $this->_request  = $request;
        $this->_response = $response;
        $this->_headers  = $headers;
    }

    /**
     * This function outputs the given $data as valid CSV to the client
     * and sets the HTTP Response Code to the given $statusCode.
     *
     * @param SlimBootstrap\DataObject[] $data       The data to output to
     *                                                   the client
     * @param int                            $statusCode The status code to set
     *                                                   in the response
     */
    public function write($data, $statusCode = 200)
    {
        $result = array();

        if (true === \is_array($data)) {
            $this->_normalizeAll($data);
            $identifiers = null;
            foreach ($data as $entry) {
                $this->_buildStructure($entry, $entry->getIdentifiers(), 0, $result);
            }
        } else {
            $this->_buildStructure($this->_normalizeOne($data), $data->getIdentifiers(), 0, $result);
        }

        $body = $this->_csvEncode($result);

        if (false === $body) {
            $this->_response->setStatus(500);
            $this->_response->setBody("Error encoding requested data.");
            return;
        }

        $this->_headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $this->_response->setStatus($statusCode);
        $this->_response->setBody($body);
    }

    /**
     * @param SlimBootstrap\DataObject $data  DataObject
     * @param array|null $keys
     * @return array
     */
    private function _normalizeOne(SlimBootstrap\DataObject &$data, $keys = null){
        if(null === $keys){
            $keys[] = \array_keys($data->getData());
        }
        $keys = \array_fill_keys($keys, null);

        if (\count(\array_intersect_key($keys, $data->getData())) !== \count($keys)){
            $data->updateData(\array_merge($keys, $data->getData()));
        } else {
            return;
        }
    }

    /**
     * @param SlimBootstrap\DataObject[] $data
     * @throws SlimBootstrap\Exception
     */
    private function _normalizeAll(&$data){
        $keys           = array();
        $identifierKeys    = null;

        foreach ($data as &$entry) {
            if(null === $identifierKeys){
                $identifierKeys = \array_keys($entry->getIdentifiers());
            } else {
                if(
                    $identifierKeys !=
                    \array_keys($entry->getIdentifiers())
                ){
                    throw new SlimBootstrap\Exception("Different identifiers!");
                }
            }
            $entry->updateData($this->_flatten($entry->getData()));
            $keys = \array_merge($keys, \array_keys($entry->getData()));
        }

        foreach ($data as $key => $value) {
            $this->_normalizeOne($value, $keys);
        }
    }

    /**
     * Creates a structured array for each given payload.
     *
     * @param SlimBootstrap\DataObject $data     The payload of a DataObject
     * @param array $identifiers The identifiers to build the array structure
     * @param int   $index       The index of the current element in the identifiers array
     * @param array $result      Reference of the result array to fill
     */
    private function _buildStructure(
        SlimBootstrap\DataObject $data,
        array $identifiers,
        $index,
        array &$result
    ) {
        $newIdentifiers = array();
        foreach($identifiers as $key =>$value){
            $newIdentifiers['identifier_' . $key] = $value;
        }
        $newIdentifiers = $this->_flatten($newIdentifiers);

        $tmp = \array_merge($newIdentifiers, $data->getData());
        $result[] = $tmp;
    }

    private function _flatten($origin, $namespace = null) {
        $target = array();

        foreach($origin as $key => $value){
            if(null === $namespace){
                $keyspace = $key;
            } else {
                $keyspace = $namespace . $this->_keyspaceDelimiter . $key;
            }

            if($value instanceof SlimBootstrap\DataObject){
                $target = \array_merge($target, $this->_flatten($value->getData(), $keyspace));
            } else if(true === \is_array($value)){
                $target = \array_merge($target, $this->_flatten($value, $keyspace));
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
     * @return  bool|string         Returns the CSV or false in case of an error.
     */
    protected function _csvEncode($data, $encloseAll = false)
    {
        //TODO: implement this

        if (false === \is_array($data)) {
            return false;
        }

        $returnCsv = array();

        foreach ($data as $first) {
            if (false === \is_array($first)) {
                continue;
            }
            $returnCsv[] = '# ' . \implode($this->_delimiter, \array_keys($first));
            break;
        }

        foreach ($data as $entry) {
            if (false === \is_array($entry)) {
                continue;
            }
            $returnCsv[] = $this->_dataSetToLine($entry, $encloseAll);
        }

        if (0 == \count($returnCsv)) {
            return false;
        }

        return \implode($this->_linebreak, $returnCsv);
    }

    /**
     * @param   array $fields Structured 2+-dimensional-data array
     * @param   bool $encloseAll Force enclosing every field (false)
     * @return bool|string Returns the line for $fields or false in case of an error.
     *
     * @throws SlimBootstrap\Exception
     * Adapted from:
     * @see http://php.net/manual/en/function.fputcsv.php#87120
     */
    private function _dataSetToLine(array $fields, $encloseAll = false) {
        $delimiter_esc = \preg_quote($this->_delimiter, '/');
        $enclosure_esc = \preg_quote($this->_enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null) {
                $output[] = $this->_null;
                continue;
            } else if (true === \is_array($field)) {
                // TODO: Add warning: "Payload not flattened!"
                throw new SlimBootstrap\Exception("Payload not flattened!");
                continue;
            }

            if ($encloseAll || \preg_match("/(\\s|" . $delimiter_esc . '|' . $enclosure_esc. ')/', $field)) {
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
