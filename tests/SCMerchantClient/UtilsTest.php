<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

use SpectroCoin\SCMerchantClient\Utils;

#[CoversClass(Utils::class)]
class UtilsTest extends TestCase
{
     // formatCurrency() 
    
    #[DataProvider('ValidformatCurrencyProvider')]
    #[TestDox('formatCurrency() - Test currency formatting with valid input')]
    public function testFormatCurrencyWithValidInput($input, string $expected): void{
        $this->assertSame($expected, Utils::formatCurrency($input));
    }

    public static function ValidformatCurrencyProvider(): array{

        return [
            'no decimals' => [1, '1.0'],
            'one decimal' => [0.1, '0.1'],
            'two decimals' => [0.11, '0.11'],
            'three decimals' => [0.111, '0.111'],
            'four decimals with trailing zeros' => [0.111100000, '0.1111'],
            'more than eight decimals' => [5.000000111, '5.00000011'],
        ];
    }
    
    #[DataProvider('InvalidformatCurrencyProvider')]
    #[TestDox('formatCurrency() - Test currency formatting with invalid input')]
    public function testFormatCurrencyWithInvalidInput($input): void{
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided amount must be numeric.');

        Utils::formatCurrency($input);
    }


    public static function invalidFormatCurrencyProvider(): array
    {
        return [
            'non-numeric string' => ['abc'],
            'array input'        => [[1, 2, 3]],
            'object input'       => [new \stdClass()],
            'empty string'       => [''],
        ];
    }

    // encryptAuthData() and decryptAuthData() roundtrip

    #[DataProvider('validAuthDataProvider')]
    #[TestDox('encryptAuthData() & decryptAuthData() - Test data encryption and decryption')]
    public function testEncryptDecryptAuthData(string $data, string $encryptionKey, string $expected){
        $encryptedData = Utils::encryptAuthData($data, $encryptionKey);
        $decryptedData = Utils::decryptAuthData($encryptedData, $encryptionKey);

        $this->assertSame($expected, $decryptedData);
    }

    public static function validAuthDataProvider(): array 
    {
        $testEncryptionKey = 'secretKey123';
        return[
            'Round-trip with normal text'=>['Hello world!', $testEncryptionKey, 'Hello world!'],
            'Round-trip with an empty string'=>['', $testEncryptionKey, ''],
            'Round-trip with special characters'=>['P@$$w0rd!#%^&', $testEncryptionKey, 'P@$$w0rd!#%^&'],
            'Round-trip with Unicode text'=>['こんにちは世界', $testEncryptionKey, 'こんにちは世界'],
            'Round-trip with a long text'=>['But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?',
             $testEncryptionKey,
            'But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you a complete account of the system, and expound the actual teachings of the great explorer of the truth, the master-builder of human happiness. No one rejects, dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know how to pursue pleasure rationally encounter consequences that are extremely painful. Nor again is there anyone who loves or pursues or desires to obtain pain of itself, because it is pain, but because occasionally circumstances occur in which toil and pain can procure him some great pleasure. To take a trivial example, which of us ever undertakes laborious physical exercise, except to obtain some advantage from it? But who has any right to find fault with a man who chooses to enjoy a pleasure that has no annoying consequences, or one who avoids a pain that produces no resultant pleasure?'],
            'Round-trip with numeric string as data'=>['2.50', $testEncryptionKey, '2.50'],
            'Round-trip with encryption key containing special characters'=>['Hello world!', 'spécîålKey!', 'Hello world!'],
            'Round-trip with binary data'=>['\x00\xFF\xFE\xFD', $testEncryptionKey, '\x00\xFF\xFE\xFD'],
        ];
    }

    #[TestDox('decryptAuthData() - Test decryption with incorrect key')]
    public function testDecryptWithIncorrectKey(): void {
        $data = "Sensitive Data";
        $correctKey = "secretKey123";
        $wrongKey = "wrongKey";
        $encrypted = Utils::encryptAuthData($data, $correctKey);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed: Invalid encryption key or corrupted data.');
        
        Utils::decryptAuthData($encrypted, $wrongKey);
    }

    #[TestDox('encryptAuthData() - Encrypted output format check')]
    public function testEncryptedOutputFormat(): void {
        $data = "Hello, World!";
        $encryptionKey = "secretKey123";
        $encrypted = Utils::encryptAuthData($data, $encryptionKey);
        $this->assertNotSame($data, $encrypted, "Encrypted data should not equal the plain text.");
        
        $decoded = base64_decode($encrypted);
        
        $this->assertStringContainsString('::', $decoded, "Decoded encrypted data should contain the '::' delimiter.");
        
        $parts = explode('::', $decoded);
        $this->assertCount(2, $parts, "Decoded encrypted data should be split into exactly two parts by '::'.");
    }

    //  sanitizeUrl()

    #[DataProvider('urlDataProvider')]
    #[TestDox('sanitizeUrl() - Test URL sanitization')]
    public function testSanitizeUrl($url, $expected): void{
        $sanitizedUrl = Utils::sanitizeUrl($url);
        $this->assertSame($expected, $sanitizedUrl);
    }

    public static function urlDataProvider(): array{

        return[
            'Input is null' => [null, null],
            'Valid URL without extraneous characters' => ['https://example.com', 'https://example.com'],
            'Valid URL with leading/trailing whitespace' => [' https://example.com ', 'https://example.com'],
            'URL containing spaces and illegal characters' => ['https://exa mple.com/path?arg=<script>', 'https://example.com/path?arg=script'],
            'Empty string input' => ['', ''],
            'Non-string numeric input (should be converted to string)' => [12345, '12345'],
            'URL with unusual/invalid characters that may be stripped out' => ['ht^tp://example.com', 'http://example.com'],
        ];
    }

    //  generateRandomStr()

    #[DataProvider('validRandomStrDataProvider')]
    #[TestDox('generateRandomStr() - Test URL sanitization')]
    public function testValidGenerateRandomStr($length): void{
        $result = Utils::generateRandomStr($length);
        $this->assertSame($length, strlen($result));
    }


    public static function validRandomStrDataProvider(): array{

        return [
            'length 0' => [0],
            'length 10' => [10],
            'length 40' => [32],
            'length 32' => [32],
        ];
    }

    #[DataProvider('invalidRandomStrDataProvider')]
    #[TestDox('generateRandomStr() - throws exception for invalid input')]
    public function testGenerateRandomStrWithInvalidInput($invalidInput): void {
        $this->expectException(\InvalidArgumentException::class);
        Utils::generateRandomStr($invalidInput);
    }


    public static function invalidRandomStrDataProvider(): array {
        return [
            'Negative integer' => [-5],
            'Float value' => [5.5],
            'String input' => ['ten'],
            'Array input' => [[10]],
            'Object input' => [new \stdClass()],
            'Null input' => [null],
        ];
    }

    // sanitize_text_field()

    #[DataProvider('textFieldDataProvider')]
    #[TestDox('sanitize_text_field() - Sanitizes text input')]
    public function testSanitizeTextField($inputString, $expected): void {
        $this->assertSame($expected, Utils::sanitize_text_field($inputString));
    }

    public static function textFieldDataProvider(): array{
        return [
            'Simple valid text' => ['Hello world from SpectroCoin!','Hello world from SpectroCoin!'],
            'Invalid UTF-8 sequence' => ["\xC0\xAF",""],
            'HTML <span> tag removal' => ['<span>testing html tags</span>','testing html tags'],
            'HTML <a> tag removal' => ['<a>testing html tags</a>','testing html tags'],
            'HTML <h2> tag removal' => ['<h2>testing html tags</h2>','testing html tags'],
            'PHP tags removal' => ['<?php> testing html tags ?>',''],
            'Conversion of a single less-than character' => ['I < 3 coding','I &lt; 3 coding'],
            'Removal of line breaks' => ["Line1\nLine2",'Line1 Line2'],
            'Removal of tab characters' => ["Hello\tWorld",'Hello World'],
            'Removal of percent-encoded characters' => ['Hello%20World','HelloWorld'],
            'Combination of multiple sanitizations' => ["<b>Hello</b> %20World!\nI < 3 coding ",'Hello World! I &lt; 3 coding'],
            'Non-valid percent-encoded sequence remains' => ['Test%ZZing','Test%ZZing'],
            'Removal of non-ASCII characters' => ['Café','Caf'],
            'Input with only whitespace' => [' ',''],
            'HTML comment removal' => ['Hello <!-- comment --> World','Hello World'],
            'Multiple consecutive percent-encoded sequences' => ['100%20%30%20%40','100'],
            'Partial percent-encoded sequence remains' => ['Test%2','Test%2'],
            'Mixed-case percent-encoded sequence removal' => ['Data%2FTest','DataTest'],
        ];
    }
}   