<?php

namespace Tests\Unit;

use App\Enums\Bank;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BankTest extends TestCase
{
    public function test_every_bank_has_a_logo_file_and_a_label(): void
    {
        foreach (Bank::cases() as $bank) {
            $this->assertFileExists(public_path("images/banks/{$bank->value}.svg"), $bank->value);
            $this->assertNotSame('', $bank->label());
        }
    }

    public function test_every_logo_file_belongs_to_a_bank(): void
    {
        $slugs = array_map(fn (Bank $bank) => $bank->value, Bank::cases());

        foreach (glob(public_path('images/banks/*.svg')) as $file) {
            $this->assertContains(basename($file, '.svg'), $slugs);
        }
    }

    #[DataProvider('names')]
    public function test_it_recognises_how_people_write_a_bank(?string $text, ?Bank $expected): void
    {
        $this->assertSame($expected, Bank::tryFromText($text));
    }

    public static function names(): array
    {
        return [
            'persian with prefix' => ['بانک ملت', Bank::Mellat],
            'arabic letters' => ['بانك ملي', Bank::Melli],
            'with tashdid' => ['ملّت', Bank::Mellat],
            'half space' => ['بانک کارآفرین', Bank::Karafarin],
            'slug' => ['mellat', Bank::Mellat],
            'english name' => ['Bank Saderat', Bank::Saderat],
            'sms parser name' => ['blu', Bank::Blubank],
            'blu in persian' => ['بلو', Bank::Blubank],
            'longest alias wins' => ['بانک توسعه صادرات ایران', Bank::ToseeSaderat],
            'shorter alias' => ['مؤسسه اعتباری توسعه', Bank::Tosee],
            'two words' => ['پست بانک', Bank::Postbank],
            'inside a sentence' => ['حساب بانک سامان من', Bank::Saman],
            'unknown' => ['یک بانک ناشناخته', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    public function test_merged_banks_are_valid_but_hidden_from_the_picker(): void
    {
        $values = array_column(Bank::options(), 'value');

        $this->assertContains('melli', $values);
        $this->assertNotContains('ansar', $values);
        $this->assertNotContains('bank-markazi', $values);
        $this->assertSame(Bank::Ansar, Bank::from('ansar'));
        $this->assertCount(count($values), array_unique($values));
    }

    public function test_aliases_never_collide_between_banks(): void
    {
        $seen = [];

        foreach (Bank::cases() as $bank) {
            foreach (array_unique(array_map(Bank::normalize(...), $bank->aliases())) as $alias) {
                $this->assertNotSame('', $alias, "{$bank->value} has an empty alias");
                $this->assertArrayNotHasKey($alias, $seen, "'{$alias}' is an alias of both {$bank->value} and ".($seen[$alias] ?? ''));
                $seen[$alias] = $bank->value;
            }
        }
    }
}
