<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ZoomSearchController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ZoomSearchController::class)]
final class ZoomSearchControllerTest extends AbstractTestCase
{
    public function testZoomSearchController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new ZoomSearchController(
            $response,
            $template,
            new Search($dbi),
            new Relation($dbi),
            $dbi,
            new DbTableExists($dbi),
        );
        $controller($request);

        $expected = $template->render('table/zoom_search/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'goto' => 'index.php?route=/sql&server=2&lang=en',
            'self' => $controller,
            'geom_column_flag' => false,
            'column_names' => ['id', 'name', 'datetimefield'],
            'data_label' => 'name',
            'keys' => [],
            'criteria_column_names' => null,
            'criteria_column_types' => null,
            'max_plot_limit' => 500,
        ]);

        $this->assertSame($expected, $response->getHTMLResult());
    }

    public function testChangeTableInfoAction(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $_POST['field'] = 'datetimefield';

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['change_tbl_info' => '1']);

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new ZoomSearchController(
            $response,
            $template,
            new Search($dbi),
            new Relation($dbi),
            $dbi,
            new DbTableExists($dbi),
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $operators = <<<'HTML'
<select class="column-operator" id="ColumnOperator0" name="criteriaColumnOperators[0]">
  <option value="=">=</option><option value="&gt;">&gt;</option><option value="&gt;=">&gt;=</option><option value="&lt;">&lt;</option><option value="&lt;=">&lt;=</option><option value="!=">!=</option><option value="LIKE">LIKE</option><option value="LIKE %...%">LIKE %...%</option><option value="NOT LIKE">NOT LIKE</option><option value="NOT LIKE %...%">NOT LIKE %...%</option><option value="IN (...)">IN (...)</option><option value="NOT IN (...)">NOT IN (...)</option><option value="BETWEEN">BETWEEN</option><option value="NOT BETWEEN">NOT BETWEEN</option>
</select>

HTML;
        // phpcs:enable

        $value = <<<'HTML'
                        <input
                    type="text"
        name="criteriaValues[0]"
        data-type="DATETIME"
         onfocus="return verifyAfterSearchFieldChange(0, '#zoom_search_form')"
        size="40"
        class="textfield datetimefield"
        id="fieldID_0"
        >

HTML;

        $expected = [
            'field_type' => 'datetime',
            'field_collation' => '',
            'field_operators' => $operators,
            'field_value' => $value,
        ];

        $this->assertSame($expected, $response->getJSONResult());
    }
}
