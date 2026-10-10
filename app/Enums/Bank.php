<?php

namespace App\Enums;

/**
 * Iranian banks with a logo in public/images/banks/{value}.svg (see scripts/bank-logos.mjs).
 * The value is the logo's file name. Case order is the display order: popular banks first.
 */
enum Bank: string
{
    case Melli = 'melli';
    case Mellat = 'mellat';
    case Saderat = 'saderat';
    case Tejarat = 'tejarat';
    case Sepah = 'sepah';
    case Parsian = 'parsian';
    case Pasargad = 'pasargad';
    case Saman = 'saman';
    case Keshavarzi = 'keshavarzi';
    case Maskan = 'maskan';
    case Refah = 'refah';
    case EghtesadNovin = 'eghtesad-novin';
    case Karafarin = 'karafarin';
    case Shahr = 'shahr';
    case Ayandeh = 'ayandeh';
    case Sina = 'sina';
    case Postbank = 'postbank';
    case Blubank = 'blubank';
    case Bankino = 'bankino';
    case IranZamin = 'iran-zamin';
    case Dey = 'dey';
    case Sarmayeh = 'sarmayeh';
    case Gardeshgari = 'gardeshgari';
    case KhavarMianeh = 'khavar-mianeh';
    case SanatMadan = 'sanat-madan';
    case ToseeSaderat = 'tosee-saderat';
    case ToseeTaavon = 'tosee-taavon';
    case Resalat = 'resalat';
    case MehrIran = 'mehr-iran';
    case Melall = 'melall';
    case Noor = 'noor';
    case Caspian = 'caspian';
    case Tosee = 'tosee';
    case TaavonEslami = 'taavon-eslami';
    case IranVenezuela = 'iran-venezuela';
    case IranEurope = 'iran-europe';
    case Futurebank = 'futurebank';

    // Merged into other banks, or not a retail bank: still valid for existing data, hidden from the picker.
    case Ansar = 'ansar';
    case Ghavamin = 'ghavamin';
    case Hekmat = 'hekmat';
    case Kosar = 'kosar';
    case MehrEghtesad = 'mehr-eghtesad';
    case BankMarkazi = 'bank-markazi';
    case StandardChartered = 'standard-chartered';

    public function label(): string
    {
        return match ($this) {
            self::Melli => __('بانک ملی ایران'),
            self::Mellat => __('بانک ملت'),
            self::Saderat => __('بانک صادرات ایران'),
            self::Tejarat => __('بانک تجارت'),
            self::Sepah => __('بانک سپه'),
            self::Parsian => __('بانک پارسیان'),
            self::Pasargad => __('بانک پاسارگاد'),
            self::Saman => __('بانک سامان'),
            self::Keshavarzi => __('بانک کشاورزی'),
            self::Maskan => __('بانک مسکن'),
            self::Refah => __('بانک رفاه کارگران'),
            self::EghtesadNovin => __('بانک اقتصاد نوین'),
            self::Karafarin => __('بانک کارآفرین'),
            self::Shahr => __('بانک شهر'),
            self::Ayandeh => __('بانک آینده'),
            self::Sina => __('بانک سینا'),
            self::Postbank => __('پست بانک ایران'),
            self::Blubank => __('بلوبانک'),
            self::Bankino => __('بانکینو'),
            self::IranZamin => __('بانک ایران زمین'),
            self::Dey => __('بانک دی'),
            self::Sarmayeh => __('بانک سرمایه'),
            self::Gardeshgari => __('بانک گردشگری'),
            self::KhavarMianeh => __('بانک خاورمیانه'),
            self::SanatMadan => __('بانک صنعت و معدن'),
            self::ToseeSaderat => __('بانک توسعه صادرات'),
            self::ToseeTaavon => __('بانک توسعه تعاون'),
            self::Resalat => __('بانک قرض‌الحسنه رسالت'),
            self::MehrIran => __('بانک قرض‌الحسنه مهر ایران'),
            self::Melall => __('مؤسسه اعتباری ملل'),
            self::Noor => __('مؤسسه اعتباری نور'),
            self::Caspian => __('مؤسسه اعتباری کاسپین'),
            self::Tosee => __('مؤسسه اعتباری توسعه'),
            self::TaavonEslami => __('تعاون اسلامی'),
            self::IranVenezuela => __('بانک مشترک ایران و ونزوئلا'),
            self::IranEurope => __('بانک ایران و اروپا'),
            self::Futurebank => __('فیوچر بانک'),
            self::Ansar => __('بانک انصار'),
            self::Ghavamin => __('بانک قوامین'),
            self::Hekmat => __('بانک حکمت ایرانیان'),
            self::Kosar => __('مؤسسه اعتباری کوثر'),
            self::MehrEghtesad => __('بانک مهر اقتصاد'),
            self::BankMarkazi => __('بانک مرکزی'),
            self::StandardChartered => __('استاندارد چارترد'),
        };
    }

    /** Whether new accounts can pick this bank (merged and non-retail banks stay valid but hidden). */
    public function isSelectable(): bool
    {
        return ! in_array($this, [self::Ansar, self::Ghavamin, self::Hekmat, self::Kosar, self::MehrEghtesad, self::BankMarkazi, self::StandardChartered], true);
    }

    /**
     * Names people type for this bank, already normalised (see normalize()).
     * The English slug is always an alias; only the extra spellings are listed.
     *
     * @return list<string>
     */
    public function aliases(): array
    {
        $names = match ($this) {
            self::Melli => ['ملی', 'meli'],
            self::Mellat => ['ملت'],
            self::Saderat => ['صادرات'],
            self::Tejarat => ['تجارت'],
            self::Sepah => ['سپه'],
            self::Parsian => ['پارسیان'],
            self::Pasargad => ['پاسارگاد'],
            self::Saman => ['سامان'],
            self::Keshavarzi => ['کشاورزی'],
            self::Maskan => ['مسکن'],
            self::Refah => ['رفاه'],
            self::EghtesadNovin => ['اقتصاد نوین', 'eghtesadnovin'],
            self::Karafarin => ['کارآفرین', 'کارافرین'],
            self::Shahr => ['شهر'],
            self::Ayandeh => ['آینده', 'اینده'],
            self::Sina => ['سینا'],
            self::Postbank => ['پست بانک', 'پستبانک'],
            self::Blubank => ['بلو', 'بلو بانک', 'blu', 'blu bank'],
            self::Bankino => ['بانکینو'],
            self::IranZamin => ['ایران زمین', 'iranzamin'],
            self::Dey => ['دی'],
            self::Sarmayeh => ['سرمایه'],
            self::Gardeshgari => ['گردشگری'],
            self::KhavarMianeh => ['خاورمیانه', 'khavarmianeh'],
            self::SanatMadan => ['صنعت و معدن', 'sanatmadan'],
            self::ToseeSaderat => ['توسعه صادرات', 'toseesaderat'],
            self::ToseeTaavon => ['توسعه تعاون', 'toseetaavon'],
            self::Resalat => ['رسالت', 'قرض الحسنه رسالت'],
            self::MehrIran => ['مهر ایران', 'قرض الحسنه مهر ایران', 'mehriran'],
            self::Melall => ['ملل'],
            self::Noor => ['نور'],
            self::Caspian => ['کاسپین'],
            self::Tosee => ['توسعه'],
            self::TaavonEslami => ['تعاون اسلامی', 'taavoneslami'],
            self::IranVenezuela => ['ایران ونزوئلا', 'ایران و ونزوئلا', 'iranvenezuela'],
            self::IranEurope => ['ایران اروپا', 'ایران و اروپا', 'iraneurope'],
            self::Futurebank => ['فیوچر', 'future bank'],
            self::Ansar => ['انصار'],
            self::Ghavamin => ['قوامین'],
            self::Hekmat => ['حکمت'],
            self::Kosar => ['کوثر'],
            self::MehrEghtesad => ['مهر اقتصاد', 'mehreghtesad'],
            self::BankMarkazi => ['مرکزی', 'بانک مرکزی', 'bankmarkazi'],
            self::StandardChartered => ['استاندارد چارترد', 'standardchartered'],
        };

        return [str_replace('-', ' ', $this->value), $this->value, ...array_map(self::normalize(...), $names)];
    }

    /** Lower-cases, unifies Arabic letters and drops "بانک", half-spaces and punctuation so "بانک ملّت" matches "ملت". */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['ك' => 'ک', 'ي' => 'ی', 'ى' => 'ی', 'ة' => 'ه', 'ؤ' => 'و', 'أ' => 'ا', 'إ' => 'ا', "\u{200c}" => ' ', '-' => ' ', '_' => ' ']);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text; // diacritics, tatweel
        $text = preg_replace('/(?<![\p{L}])(بانک|موسسه اعتباری|مؤسسه اعتباری|bank)(?![\p{L}])/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** Finds the bank a free-text name refers to; the longest matching alias wins ("توسعه صادرات" over "توسعه"). */
    public static function tryFromText(?string $text): ?self
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $slug = self::tryFrom(trim($text));
        if ($slug) {
            return $slug;
        }

        $needle = self::normalize($text);
        if ($needle === '') {
            return null;
        }

        $best = null;
        $bestLength = 0;

        foreach (self::cases() as $bank) {
            foreach ($bank->aliases() as $alias) {
                $alias = self::normalize($alias);
                $matches = $needle === $alias || preg_match('/(?<![\p{L}\p{N}])'.preg_quote($alias, '/').'(?![\p{L}\p{N}])/u', $needle) === 1;

                if ($alias !== '' && $matches && mb_strlen($alias) > $bestLength) {
                    $best = $bank;
                    $bestLength = mb_strlen($alias);
                }
            }
        }

        return $best;
    }

    /** A known bank replaces the free-text name, so the two never disagree. */
    public static function withoutDuplicateName(array $data): array
    {
        if (! empty($data['bank'])) {
            $data['bank_name'] = null;
        }

        return $data;
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_values(array_map(
            fn (self $bank) => ['value' => $bank->value, 'label' => $bank->label()],
            array_filter(self::cases(), fn (self $bank) => $bank->isSelectable()),
        ));
    }
}
