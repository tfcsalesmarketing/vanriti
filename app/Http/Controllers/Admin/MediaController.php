<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use ZipArchive;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $media = Media::query()
            ->with('admin')
            ->when($request->input('q'), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('file_name', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,webp,gif', 'max:5120'],
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string', 'max:150'],
        ]);

        $files = $request->file('images', []);
        $names = $request->input('names', []);
        $uploaded = 0;

        foreach ($files as $index => $file) {
            try {
                $given = trim((string) ($names[$index] ?? ''));
                $name = filled($given)
                    ? $given
                    : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $path = $file->store('media', 's3');

                Media::create([
                    'name' => $name,
                    'file_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 's3',
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'admin_id' => auth('admin')->id(),
                ]);

                $uploaded++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($uploaded === 0) {
            return back()->with('error', 'No images could be uploaded. Please try again.');
        }

        $message = $uploaded === 1
            ? 'Image uploaded successfully.'
            : "{$uploaded} images uploaded successfully.";

        return back()->with('success', $message);
    }

    public function destroy(Media $media): RedirectResponse
    {
        try {
            if ($media->path && ! str_starts_with($media->path, 'http')) {
                Storage::disk($media->disk ?? 's3')->delete($media->path);
            }

            $media->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }

        return back()->with('success', 'Image deleted successfully.');
    }

    public function export()
    {
        $media = Media::query()->latest()->get(['name', 'path', 'disk']);

        $rows = [['Image Name', 'Link']];
        foreach ($media as $item) {
            $rows[] = [$item->name, $item->url];
        }

        $filename = 'media_library_'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($rows) {
            $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($tmp === false) {
                return;
            }

            try {
                $zip = new ZipArchive;

                if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    return;
                }

                $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
                $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
                $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
                $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
                $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
                $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheet($rows));
                $zip->close();

                $handle = fopen($tmp, 'rb');
                if ($handle !== false) {
                    fpassthru($handle);
                    fclose($handle);
                }
            } finally {
                @unlink($tmp);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function xlsxColumn(int $index): string
    {
        $letter = '';

        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = (int) floor($index / 26) - 1;
        }

        return $letter;
    }

    private function xlsxWorksheet(array $rows): string
    {
        $out = [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">',
            '<sheetViews><sheetView workbookViewId="0"/></sheetViews>',
            '<sheetFormatPr defaultRowHeight="15"/>',
            '<cols><col min="1" max="1" width="40" customWidth="1"/><col min="2" max="2" width="90" customWidth="1"/></cols>',
            '<sheetData>',
        ];

        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 1;
            $out[] = '<row r="'.$rowNum.'">';

            foreach ($row as $colIndex => $cell) {
                $ref = $this->xlsxColumn($colIndex).$rowNum;
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $value = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $out[] = '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$value.'</t></is></c>';
            }

            $out[] = '</row>';
        }

        $out[] = '</sheetData>';
        $out[] = '</worksheet>';

        return implode('', $out);
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Media Library" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
