<?php

namespace Tests\Unit;

use App\Models\SmsPattern;
use App\Services\Sms\BankSmsParser;
use PHPUnit\Framework\TestCase;

class BankSmsParserTest extends TestCase
{
    private BankSmsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BankSmsParser();
    }

    // ── Normalization ───────────────────────────────────────────

    public function test_normalizes_persian_digits(): void
    {
        $this->assertSame('0123456789', $this->parser->normalizeText('۰۱۲۳۴۵۶۷۸۹'));
    }

    public function test_normalizes_arabic_digits(): void
    {
        $this->assertSame('0123456789', $this->parser->normalizeText('٠١٢٣٤٥٦٧٨٩'));
    }

    public function test_normalizes_arabic_persian_characters(): void
    {
        $this->assertSame('یاک', $this->parser->normalizeText('ياك'));
    }

    // ── OTP detection ───────────────────────────────────────────

    public function test_blu_otp_is_skipped(): void
    {
        $sms = "بلو\nبفرمایید رمز پویا\nخرید\nهمراه كسب و كارهاي هوشمند\nمبلغ: 457,235 ریال\nرمز: 904438 \n23:57";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_resalat_otp_is_skipped(): void
    {
        $sms = "محرمانه\nخرید\nخرید هاست و ثبت دامن\n3,399,000\nرمز:59864\n1405/02/19-18:31:06";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_gardeshgari_transfer_otp_is_skipped(): void
    {
        $sms = "بانک گردشگري\nانتقال به\n123456*7890\nمبلغ:3,750,000\nرمز:31432\n654321*0987\n1405/03/18-17:43:37";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_gardeshgari_purchase_otp_is_skipped(): void
    {
        $sms = "بانک گردشگري\nخرید\nدكترتو\nمبلغ:329,450\nرمز:02461\n654321*0987\n1405/03/17-20:08:39";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_sepah_payment_otp_is_skipped(): void
    {
        $sms = "بانک سپه\nپرداخت\nبانک سپه\nمبلغ 36,946,401 ريال\nرمز: 192372\nاعتبار 22:55:55";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_sepah_login_otp_is_skipped(): void
    {
        $sms = "بانک سپه\nورود\nاينترنت بانک\nرمز: 375903\nزمان اعتبار 22:52:49";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    // ── بلوبانک (Blu) ───────────────────────────────────────────

    public function test_blu_deposit(): void
    {
        $sms = "بلو\nواریز پول\nعلی عزیز، 1,000,000 ریال به حساب شما نشست.\nموجودی: 2,042,017 ریال\n۲۲:۴۵\n۱۴۰۵.۰۴.۰۳";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(1000000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame('IRR', $parsed->currency);
        $this->assertSame(2042017.0, $parsed->balanceAfter);
        $this->assertSame('blu', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
        $this->assertNotNull($parsed->occurredAt);
    }

    public function test_blu_withdrawal(): void
    {
        $sms = "بلو\nبرداشت پول\nعلی عزیز، 1,508,000 ریال از حساب شما پرید.\nموجودی: 2,042,017 ریال\n۲۱:۲۲\n۱۴۰۵.۰۴.۰۳";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(1508000.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame(2042017.0, $parsed->balanceAfter);
        $this->assertSame('blu', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
    }

    public function test_blu_welcome_is_unparsed(): void
    {
        $sms = "بلو\nعلی عزیز خوش آمدید.\n12:38\n1405.04.04";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    // ── بانک رسالت (Resalat) ────────────────────────────────────

    public function test_resalat_deposit(): void
    {
        $sms = " 10.1234567.1\n+33,000,000 \n04/03_12:14\nمانده: 33,000,000 ";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(33000000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame(33000000.0, $parsed->balanceAfter);
        $this->assertSame('resalat', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
        $this->assertNotNull($parsed->occurredAt);
    }

    public function test_resalat_withdrawal(): void
    {
        $sms = "10.1234567.1\n-32,844,939 \n04/03_12:14\nمانده: 155,061 ";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(32844939.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame(155061.0, $parsed->balanceAfter);
        $this->assertSame('resalat', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
    }

    public function test_resalat_fee(): void
    {
        $sms = "10.1234567.1\n-39,000 \n03/21_10:41\nمانده: 155,061 \nکارمزد پیامک فروردین ماه 1405";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(39000.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame(155061.0, $parsed->balanceAfter);
        $this->assertSame('resalat', $parsed->bankName);
    }

    public function test_resalat_login_notification_is_unparsed(): void
    {
        $sms = 'ورود به وی بنک - موبایل در تاریخ 1405/04/03 ساعت 12:12 بانک قرض‌الحسنه رسالت';
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    // ── بانک گردشگری (Gardeshgari) ──────────────────────────────

    public function test_gardeshgari_withdrawal(): void
    {
        $sms = "*بانك گردشگری*\nکارت\nبرداشت از: 2311.7007.1234567.1\nمبلغ: 10,314,510 ريال\n05/04/03_18:48\nموجودي: 266,330,139 ريال";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(10314510.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame('IRR', $parsed->currency);
        $this->assertSame(266330139.0, $parsed->balanceAfter);
        $this->assertSame('gardeshgari', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
        $this->assertNotNull($parsed->occurredAt);
    }

    public function test_gardeshgari_deposit(): void
    {
        $sms = "*بانك گردشگری*\nکارت\nواريز به: 2311.7007.1234567.1\nمبلغ: 12,000,000 ريال\n05/04/02_19:00\nموجودي: 309,663,249 ريال";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(12000000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame(309663249.0, $parsed->balanceAfter);
        $this->assertSame('gardeshgari', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
    }

    public function test_gardeshgari_named_deposit(): void
    {
        $sms = "*بانك گردشگری*\nسي و سه پل\nواريز به: 2311.7007.1234567.1\nمبلغ: 381,182,210 ريال\n05/04/02_14:42\nموجودي: 387,697,849 ريال";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(381182210.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame(387697849.0, $parsed->balanceAfter);
    }

    public function test_gardeshgari_fee(): void
    {
        $sms = "*بانك گردشگری*\nکارمزد ارسال پيامک\nبرداشت از: 2311.7007.1234567.1\nمبلغ: 440,000 ريال\n05/03/18_17:32\nموجودي: 140,536,839 ريال";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(440000.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame(140536839.0, $parsed->balanceAfter);
    }

    // ── بانک سپه (Sepah) ────────────────────────────────────────

    public function test_sepah_deposit(): void
    {
        $sms = "بانک سپه\nواريز:10,200,000ريال\nحساب:1234567890123\nمانده:36,100,000\n3/22-19:46";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(10200000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame('IRR', $parsed->currency);
        $this->assertSame(36100000.0, $parsed->balanceAfter);
        $this->assertSame('sepah', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
        $this->assertNotNull($parsed->occurredAt);
    }

    public function test_sepah_withdrawal_without_rial(): void
    {
        $sms = "بانک سپه\nبرداشت:36,180,724\nحساب :1234567890123\nمانده:66,875\n2/9-20:58\nدريافت اتوماتيک  قسط مور";
        $parsed = $this->parser->parse($sms);

        $this->assertSame(36180724.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame(66875.0, $parsed->balanceAfter);
        $this->assertSame('sepah', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
    }

    public function test_sepah_login_notification_is_unparsed(): void
    {
        $sms = "بانک سپه\nورود به اينترنت بانک\n1405/3/22\n21:00";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_sepah_failed_login_notification_is_unparsed(): void
    {
        $sms = "بانک سپه\nورود ناموفق به اينترنت بانک\n1405/4/3\n12:15";
        $parsed = $this->parser->parse($sms);
        $this->assertFalse($parsed->isParsed());
    }

    // ── Generic / fallback ──────────────────────────────────────

    public function test_generic_deposit_with_persian_digits(): void
    {
        $parsed = $this->parser->parse('برداشت مبلغ ۲۵۰,۰۰۰ ریال از کارت *1234');
        $this->assertSame(250000.0, $parsed->amount);
        $this->assertSame('expense', $parsed->type);
        $this->assertSame('IRR', $parsed->currency);
        $this->assertSame('1234', $parsed->cardLastFour);
    }

    public function test_generic_deposit_amount_and_type(): void
    {
        $parsed = $this->parser->parse('واریز مبلغ 1,500,000 ریال به حساب *5678');
        $this->assertSame(1500000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame('5678', $parsed->cardLastFour);
    }

    public function test_detects_toman_currency(): void
    {
        $parsed = $this->parser->parse('واریز مبلغ 50,000 تومان');
        $this->assertSame('IRT', $parsed->currency);
    }

    public function test_extracts_balance(): void
    {
        $parsed = $this->parser->parse('برداشت مبلغ 100,000 ریال مانده: 2,500,000');
        $this->assertSame(2500000.0, $parsed->balanceAfter);
    }

    public function test_extracts_tracking_number(): void
    {
        $parsed = $this->parser->parse('واریز مبلغ 200,000 ریال پیگیری: 123456789');
        $this->assertSame('123456789', $parsed->trackingNumber);
    }

    public function test_card_mask_with_dots(): void
    {
        $parsed = $this->parser->parse('واریز 500,000 ریال به کارت ....4321');
        $this->assertSame('4321', $parsed->cardLastFour);
    }

    public function test_medium_confidence_when_amount_and_type_found(): void
    {
        $parsed = $this->parser->parse('واریز 1,000,000 ریال');
        $this->assertSame('medium', $parsed->confidence);
        $this->assertTrue($parsed->isParsed());
    }

    public function test_unparseable_message_returns_null(): void
    {
        $parsed = $this->parser->parse('سلام. سرویس شما فعال شد.');
        $this->assertNull($parsed->amount);
        $this->assertNull($parsed->type);
        $this->assertFalse($parsed->isParsed());
    }

    public function test_mixed_persian_arabic_digits(): void
    {
        $parsed = $this->parser->parse('واریز ۱,٢۵۰,۰۰٠ ریال');
        $this->assertSame(1250000.0, $parsed->amount);
    }

    public function test_arabic_thousand_separator(): void
    {
        $parsed = $this->parser->parse('واریز 1٬250٬000 ریال');
        $this->assertSame(1250000.0, $parsed->amount);
    }

    public function test_user_defined_pattern(): void
    {
        $pattern = new SmsPattern([
            'bank_name' => 'mellat',
            'pattern' => '(?<type>واریز|برداشت).*?(?<amount>[\d,]+)\s*ریال.*?مانده[:\s]*(?<balance>[\d,]+).*?کارت\s*(?<card>\d{4})',
            'debit_keywords' => ['برداشت'],
            'credit_keywords' => ['واریز'],
        ]);
        $pattern->id = 1;

        $parsed = $this->parser->parse(
            'واریز مبلغ 2,000,000 ریال مانده: 5,000,000 کارت 9876',
            collect([$pattern]),
        );

        $this->assertSame(2000000.0, $parsed->amount);
        $this->assertSame('income', $parsed->type);
        $this->assertSame(5000000.0, $parsed->balanceAfter);
        $this->assertSame('9876', $parsed->cardLastFour);
        $this->assertSame('mellat', $parsed->bankName);
        $this->assertSame('high', $parsed->confidence);
        $this->assertSame(1, $parsed->patternId);
    }

    public function test_user_pattern_fallback_to_generic(): void
    {
        $pattern = new SmsPattern([
            'bank_name' => 'mellat',
            'pattern' => 'NOMATCHPATTERN',
            'debit_keywords' => ['برداشت'],
            'credit_keywords' => ['واریز'],
        ]);
        $pattern->id = 1;

        $parsed = $this->parser->parse('واریز 500,000 ریال', collect([$pattern]));

        $this->assertSame(500000.0, $parsed->amount);
        $this->assertNull($parsed->patternId);
    }

    public function test_to_array_returns_expected_keys(): void
    {
        $parsed = $this->parser->parse('واریز 1,000,000 ریال');
        $array = $parsed->toArray();

        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('balance_after', $array);
        $this->assertArrayHasKey('confidence', $array);
    }

    public function test_fee_keyword_detected_as_expense(): void
    {
        $parsed = $this->parser->parse('کارمزد 5,000 ریال از حساب شما کسر شد');
        $this->assertSame('expense', $parsed->type);
    }

    public function test_extracts_reference_keyword(): void
    {
        $parsed = $this->parser->parse('واریز 200,000 ریال ارجاع: 987654321');
        $this->assertSame('987654321', $parsed->trackingNumber);
    }
}
