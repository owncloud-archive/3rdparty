<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Helper;

use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\StreamOutput;

class TableHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $stream;

    protected function setUp()
    {
        $this->stream = fopen('php://memory', 'r+');
    }

    protected function tearDown()
    {
        fclose($this->stream);
        $this->stream = null;
    }

    /**
     * @dataProvider testRenderProvider
     */
    public function testRender($headers, $rows, $layout, $expected)
    {
        $table = new TableHelper();
        $table
            ->setHeaders($headers)
            ->setRows($rows)
            ->setLayout($layout)
        ;
        $table->render($output = $this->getOutputStream());

        $this->assertEquals($expected, $this->getOutputContent($output));
    }

    /**
     * @dataProvider testRenderProvider
     */
    public function testRenderAddRows($headers, $rows, $layout, $expected)
    {
        $table = new TableHelper();
        $table
            ->setHeaders($headers)
            ->addRows($rows)
            ->setLayout($layout)
        ;
        $table->render($output = $this->getOutputStream());

        $this->assertEquals($expected, $this->getOutputContent($output));
    }

    /**
     * @dataProvider testRenderProvider
     */
    public function testRenderAddRowsOneByOne($headers, $rows, $layout, $expected)
    {
        $table = new TableHelper();
        $table
            ->setHeaders($headers)
            ->setLayout($layout)
        ;
        foreach ($rows as $row) {
            $table->addRow($row);
        }
        $table->render($output = $this->getOutputStream());

        $this->assertEquals($expected, $this->getOutputContent($output));
    }

    public function testRenderProvider()
    {
        return array(
            array(
                array('ISBN', 'Title', 'Author'),
                array(
                    array('99921-58-10-7', 'Divine Comedy', 'Dante Alighieri'),
                    array('9971-5-0210-0', 'A Tale of Two Cities', 'Charles Dickens'),
                    array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
                    array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
                ),
                TableHelper::LAYOUT_DEFAULT,
<<<TABLE
+---------------+--------------------------+------------------+
| ISBN          | Title                    | Author           |
+---------------+--------------------------+------------------+
| 99921-58-10-7 | Divine Comedy            | Dante Alighieri  |
| 9971-5-0210-0 | A Tale of Two Cities     | Charles Dickens  |
| 960-425-059-0 | The Lord of the Rings    | J. R. R. Tolkien |
| 80-902734-1-6 | And Then There Were None | Agatha Christie  |
+---------------+--------------------------+------------------+

TABLE
            ),
            array(
                array('ISBN', 'Title', 'Author'),
                array(
                    array('99921-58-10-7', 'Divine Comedy', 'Dante Alighieri'),
                    array('9971-5-0210-0', 'A Tale of Two Cities', 'Charles Dickens'),
                    array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
                    array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
                ),
                TableHelper::LAYOUT_BORDERLESS,
                " =============== ========================== ================== \n  ISBN            Title                      Author            \n =============== ========================== ================== \n  99921-58-10-7   Divine Comedy              Dante Alighieri   \n  9971-5-0210-0   A Tale of Two Cities       Charles Dickens   \n  960-425-059-0   The Lord of the Rings      J. R. R. Tolkien  \n  80-902734-1-6   And Then There Were None   Agatha Christie   \n =============== ========================== ================== \n"
            ),
            array(
                array('ISBN', 'Title'),
                array(
                    array('99921-58-10-7', 'Divine Comedy', 'Dante Alighieri'),
                    array('9971-5-0210-0'),
                    array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
                    array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
                ),
                TableHelper::LAYOUT_DEFAULT,
<<<TABLE
+---------------+--------------------------+------------------+
| ISBN          | Title                    |                  |
+---------------+--------------------------+------------------+
| 99921-58-10-7 | Divine Comedy            | Dante Alighieri  |
| 9971-5-0210-0 |                          |                  |
| 960-425-059-0 | The Lord of the Rings    | J. R. R. Tolkien |
| 80-902734-1-6 | And Then There Were None | Agatha Christie  |
+---------------+--------------------------+------------------+

TABLE
            ),
            array(
                array(),
                array(
                    array('99921-58-10-7', 'Divine Comedy', 'Dante Alighieri'),
                    array('9971-5-0210-0'),
                    array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
                    array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
                ),
                TableHelper::LAYOUT_DEFAULT,
<<<TABLE
+---------------+--------------------------+------------------+
| 99921-58-10-7 | Divine Comedy            | Dante Alighieri  |
| 9971-5-0210-0 |                          |                  |
| 960-425-059-0 | The Lord of the Rings    | J. R. R. Tolkien |
| 80-902734-1-6 | And Then There Were None | Agatha Christie  |
+---------------+--------------------------+------------------+

TABLE
            ),
            array(
                array('ISBN', 'Title'),
                array(),
                TableHelper::LAYOUT_DEFAULT,
<<<TABLE
+------+-------+
| ISBN | Title |
+------+-------+

TABLE
            ),
            array(
                array(),
                array(),
                TableHelper::LAYOUT_DEFAULT,
                '',
            ),
        );
    }

    public function testRenderMultiByte()
    {
        if (!function_exists('mb_strlen')) {
            $this->markTestSkipped('The "mbstring" extension is not available');
        }

        $table = new TableHelper();
        $table
            ->setHeaders(array('■■'))
            ->setRows(array(array(1234)))
            ->setLayout(TableHelper::LAYOUT_DEFAULT)
        ;
        $table->render($output = $this->getOutputStream());

        $expected =
<<<TABLE
+------+
| ■■   |
+------+
| 1234 |
+------+

TABLE;

        $this->assertEquals($expected, $this->getOutputContent($output));
    }

    protected function getOutputStream()
    {
        return new StreamOutput($this->stream, StreamOutput::VERBOSITY_NORMAL, false);
    }

    protected function getOutputContent(StreamOutput $output)
    {
        rewind($output->getStream());

        return str_replace(PHP_EOL, "\n", stream_get_contents($output->getStream()));
    }
}
