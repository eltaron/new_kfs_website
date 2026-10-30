<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use Modules\Content\Models\ExamResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use ZipArchive;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'النتائج الدراسية';
    protected static ?string $modelLabel = 'نتيجة';
    protected static ?string $pluralModelLabel = 'نتائج تالتة إعدادي';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الطالب')
                    ->schema([
                        Forms\Components\TextInput::make('seat_number')->label('رقم الجلوس')->required()->maxLength(20)->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('student_name')->label('اسم الطالب')->required()->maxLength(255),
                        Forms\Components\TextInput::make('school')->label('المدرسة')->maxLength(255),
                        Forms\Components\TextInput::make('adminstration')->label('الإدارة التعليمية')->maxLength(255),
                        Forms\Components\TextInput::make('academic_year')->label('العام الدراسي')->maxLength(255)->default('2025/2026'),
                    ])->columns(2),
                Forms\Components\Section::make('الدرجات')
                    ->schema([
                        Forms\Components\TextInput::make('total_grade')->label('المجموع الكلي')->numeric()->suffix('درجة'),
                        Forms\Components\TextInput::make('status')->label('النتيجة')->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('المواد الدراسية')
                    ->schema([
                        Forms\Components\Repeater::make('subjects')->label('')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('المادة')->required(),
                                Forms\Components\TextInput::make('grade')->label('الدرجة')->numeric()->required(),
                            ])->columns(2)->defaultItems(0)->addActionLabel('إضافة مادة'),
                    ]),
                Forms\Components\Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seat_number')->label('رقم الجلوس')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student_name')->label('اسم الطالب')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school')->label('المدرسة')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('adminstration')->label('الإدارة التعليمية')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('academic_year')->label('العام الدراسي')->toggleable(),
                Tables\Columns\TextColumn::make('total_grade')->label('المجموع')->numeric()->sortable()->color(fn ($state) => $state >= 200 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('status')->label('النتيجة')->searchable(),
            ])
            ->defaultSort('seat_number')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('النتيجة')->options(fn () => ExamResult::query()->whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status', 'status')->toArray()),
                Tables\Filters\SelectFilter::make('academic_year')->label('العام الدراسي')->options(fn () => ExamResult::query()->whereNotNull('academic_year')->where('academic_year', '!=', '')->distinct()->pluck('academic_year', 'academic_year')->toArray()),
            ])
            ->headerActions([
                Action::make('importExcel')->label('استيراد من Excel')->icon('heroicon-o-document-arrow-up')
                    ->color('success')->form([
                        Forms\Components\FileUpload::make('file')->label('ملف Excel')->required()->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->helperText('يدعم XLSX و CSV. أول صف يجب أن يحتوي على عناوين الأعمدة.'),
                        Forms\Components\Select::make('year')->label('العام الدراسي')->options(fn () => ExamResult::query()->whereNotNull('academic_year')->where('academic_year', '!=', '')->distinct()->pluck('academic_year', 'academic_year')->toArray() + ['2025/2026' => '2025/2026'])->default('2025/2026'),
                    ])->action(function (array $data) {
                        self::importFromExcel(storage_path('app/public/' . $data['file']), $data['year']);
                    }),
                Action::make('downloadTemplate')->label('تحميل نموذج Excel')->icon('heroicon-o-arrow-down-tray')
                    ->color('info')->action(fn () => response()->download(self::generateTemplate())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function generateTemplate()
    {
        $headers = ['رقم الجلوس', 'اسم الطالب', 'المدرسة', 'الإدارة التعليمية', 'المجموع الكلي', 'النتيجة'];
        $sample = ['202401', 'أحمد محمد علي', 'مدرسة كفر الشيخ الإعدادية', 'إدارة كفر الشيخ', '310', 'ناجح'];

        $path = storage_path('app/public/template_exam_results.xlsx');
        self::writeXlsx($path, [$headers, $sample]);
        return $path;
    }

    public static function importFromExcel($filePath, $year)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = array_map('str_getcsv', file($filePath));
            $headers = array_shift($rows);
        } elseif ($ext === 'xlsx') {
            $rows = self::readXlsx($filePath);
            $headers = array_shift($rows);
        } else {
            throw new \Exception('صيغة الملف غير مدعومة');
        }

        $nameCol = $seatCol = $totalCol = $schoolCol = $adminstrationCol = $statusCol = null;

        foreach ($headers as $i => $h) {
            $h = trim($h);
            if (str_contains($h, 'الاسم') || str_contains($h, 'اسم')) $nameCol = $i;
            elseif (str_contains($h, 'جلوس') || $h === 'رقم') $seatCol = $i;
            elseif (str_contains($h, 'مجموع') || str_contains($h, 'المجموع') || str_contains($h, 'total')) $totalCol = $i;
            elseif (str_contains($h, 'مدرسة') || str_contains($h, 'school')) $schoolCol = $i;
            elseif (str_contains($h, 'إدارة') || str_contains($h, 'ادارة') || str_contains($h, 'admin')) $adminstrationCol = $i;
            elseif (str_contains($h, 'نتيجة') || str_contains($h, 'حالة') || str_contains($h, 'result')) $statusCol = $i;
        }

        $imported = 0;
        foreach ($rows as $row) {
            if (empty(array_filter($row))) continue;
            $seat = trim($row[$seatCol] ?? '');
            $name = trim($row[$nameCol] ?? '');
            if (!$seat || !$name) continue;

            ExamResult::updateOrCreate(
                ['seat_number' => $seat],
                [
                    'student_name' => $name,
                    'school' => trim($row[$schoolCol] ?? ''),
                    'adminstration' => trim($row[$adminstrationCol] ?? ''),
                    'academic_year' => $year,
                    'total_grade' => $totalCol !== null ? floatval($row[$totalCol] ?? 0) : 0,
                    'status' => trim($row[$statusCol] ?? ''),
                ]
            );
            $imported++;
        }

        \Filament\Notifications\Notification::make()->title("تم استيراد $imported نتيجة بنجاح")->success()->send();
    }

    private static function readXlsx($filePath)
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) throw new \Exception('فشل فتح ملف Excel');

        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (!$xml) throw new \Exception('لا يوجد sheet1 في الملف');

        $strings = [];
        if ($shared) {
            $sxml = new SimpleXMLElement($shared);
            foreach ($sxml->si as $si) $strings[] = (string)$si->t;
        }

        $sheet = new SimpleXMLElement($xml);
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = preg_replace('/[0-9]/', '', (string)$c['r']);
                $val = (string)$c->v;
                if ((string)$c['t'] === 's') $val = $strings[(int)$val] ?? $val;
                $cells[$col] = $val;
            }
            ksort($cells);
            $rows[] = array_values($cells);
        }
        return $rows;
    }

    private static function writeXlsx($path, $rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';
        $sharedStrings = [];
        foreach ($rows as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach ($row as $c => $val) {
                $colLetter = chr(65 + $c);
                $si = array_search($val, $sharedStrings);
                if ($si === false) { $si = count($sharedStrings); $sharedStrings[] = $val; }
                $xml .= '<c r="' . $colLetter . ($r + 1) . '" t="s"><v>' . $si . '</v></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';

        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
        foreach ($sharedStrings as $s) $ssXml .= '<si><t>' . htmlspecialchars($s) . '</t></si>';
        $ssXml .= '</sst>';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) throw new \Exception('فشل إنشاء الملف');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->close();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}
