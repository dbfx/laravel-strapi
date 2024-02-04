<?php

namespace Dbfx\LaravelStrapi\Tests;

use Dbfx\LaravelStrapi\LaravelStrapi;

class LaravelStrapiTest extends TestCase
{
    public function test_single_filter_string()
    {
        $strapi = new LaravelStrapi();

        $filterString = $strapi->createFilterString([["column" => ['$eq' => 'value']]]);

        $this->assertEquals('filters[column][$eq]=value', $filterString);
    }

    public function test_multi_filter_string()
    {
        $strapi = new LaravelStrapi();

        $filterString = $strapi->createFilterString([["column" => ['$eq' => 'value']], ["column1" => ['$eq1' => 'value1']]]);

        $this->assertEquals('filters[column][$eq]=value&filters[column1][$eq1]=value1', $filterString);
    }
}
