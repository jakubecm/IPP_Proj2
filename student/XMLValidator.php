<?php

namespace IPP\Student;

class XMLValidator
{
    public static function validateXMLStructure(\DOMDocument $xmlFile, Interpreter $interpret): void
    {
        $xpath = new \DOMXPath($xmlFile);
        $instructions = $xpath->query('//instruction');
        $orderValues = [];

        foreach ($instructions as $instruction) {
            $order = $instruction->getAttribute('order');

            if (isset($orderValues[$order])) {
                // Duplicate order found
                echo "Error: Duplicate order '$order' found.\n";
                exit(32); // Use an appropriate error code or handling mechanism
            }
            if (intval($order) < 1) {
                // Negative order found
                echo "Error: Negative order '$order' found.\n";
                exit(32);
            }
            $orderValues[$order] = true;

            // Validate the sequence of arguments
            self::validateArgumentSequence($instruction);
        }

        // Validate allowed parts of the XML tree
        $invalidNodes = $xpath->query('//*[not(self::program or self::instruction or self::arg1 or self::arg2 or self::arg3)]');
        if ($invalidNodes->length > 0) {
            echo "Error: Invalid XML structure.\n";
            exit(32);
        }
    }

    private static function validateArgumentSequence(\DOMElement $instruction): void
    {
        $maxArgs = 3; // Adjust based on the maximum number of arguments you expect
        $lastArgFound = 0;

        for ($i = 1; $i <= $maxArgs; $i++) {
            $arg = $instruction->getElementsByTagName("arg$i")->item(0);
            if ($arg !== null) {
                if ($i > $lastArgFound + 1) {
                    // If there's a gap in the sequence, throw an error
                    echo "Error: arg$i exists without arg" . ($i - 1) . "\n";
                    exit(32);
                }
                $lastArgFound = $i;
            }
        }
    }
}
