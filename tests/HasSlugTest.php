<?php

namespace Zbiller\Url\Tests;

use Zbiller\Url\Options\SlugOptions;
use Zbiller\Url\Tests\Models\SlugModel;
use Zbiller\Url\Exceptions\SlugException;

class HasSlugTest extends TestCase
{
    /** @test */
    public function it_generates_a_slug_when_creating_a_record()
    {
        $this->createSlugModel();

        $this->assertEquals('test-name', $this->slugModel->slug);
    }

    /** @test */
    public function it_updates_a_slug_when_modifying_a_record()
    {
        $this->createSlugModel();

        $this->slugModel->update(['name' => 'Modified test name']);

        $this->assertEquals('modified-test-name', $this->slugModel->slug);
    }

    /** @test */
    public function it_saves_unique_slugs_for_each_record_by_default()
    {
        $this->createSlugModel();

        foreach (range(1, 10) as $i) {
            $this->createSlugModel();

            $this->assertEquals("test-name-{$i}", $this->slugModel->slug);
        }
    }

    /** @test */
    public function it_has_a_method_preventing_a_slug_from_being_generated_on_create()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->doNotGenerateSlugOnCreate();
            }
        };

        $this->createSlugModel($model);

        $this->assertEquals(null, $this->slugModel->slug);
    }

    /** @test */
    public function it_has_a_method_preventing_a_slug_from_being_generated_on_update()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->doNotGenerateSlugOnUpdate();
            }
        };

        $this->createSlugModel($model);

        $this->slugModel->update(['name' => 'Modified test name']);

        $this->assertEquals('test-name', $this->slugModel->slug);
    }

    /**
     * Not generating unique slugs and allowing duplicates is possible by customizing the HasSlug behavior, via SlugOptions.
     * SlugOptions::instance()->allowDuplicateSlugs().
     *
     * @test
     */
    public function it_has_a_method_that_allows_saving_duplicate_slugs()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->allowDuplicateSlugs();
            }
        };

        foreach (range(1, 10) as $i) {
            $this->createSlugModel($model);

            $this->assertEquals('test-name', $this->slugModel->slug);
        }
    }

    /** @test */
    public function it_has_a_method_for_manually_defining_the_word_separator()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->usingSeparator('_');
            }
        };

        $this->createSlugModel($model);

        $this->assertEquals('test_name', $this->slugModel->slug);
    }

    /** @test */
    public function it_can_generate_slugs_from_multiple_source_fields()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugFrom([
                    'name', 'other_field',
                ]);
            }
        };

        $this->createSlugModel($model);

        $this->assertEquals('test-name-other-field', $this->slugModel->slug);
    }

    /** @test */
    public function it_can_generate_language_specific_slugs()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions();
            }
        };

        $this->createSlugModel($model, ['name' => 'Güte nacht']);

        $this->assertEquals('gute-nacht', $this->slugModel->slug);

        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->usingLanguage('de');
            }
        };

        $this->createSlugModel($model, ['name' => 'Güte nacht']);

        $this->assertEquals('guete-nacht', $this->slugModel->slug);
    }

    /** @expectedException SlugException */
    public function it_expects_a_from_field_to_be_specified_in_the_options()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return SlugOptions::instance()->saveSlugTo('slug');
            }
        };

        $this->createSlugModel($model);
    }

    /** @expectedException SlugException */
    public function it_expects_a_to_field_to_be_specified_in_the_options()
    {
        $model = new class extends SlugModel {
            public function getSlugOptions() : SlugOptions
            {
                return SlugOptions::instance()->generateSlugFrom('slug');
            }
        };

        $this->createSlugModel($model);
    }
}
