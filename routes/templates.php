<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/templates', function () {
    return view('templates', [
        'templates' => [
            ['id' => 1, 'title' => 'APP Template', 'file' => 'standard-template/app-template.xlsx', 'type' => 'XLSX'],
            ['id' => 2, 'title' => 'PPMP Template', 'file' => 'standard-template/ppmp-template.xlsx', 'type' => 'XLSX'],
            ['id' => 3, 'title' => 'Project Proposal Template', 'file' => '3. project-proposal-template.docx', 'type' => 'DOCX'],
            ['id' => 4, 'title' => 'Project Brief Template', 'file' => '4. project-brief-template.docx', 'type' => 'DOCX'],
            ['id' => 5, 'title' => 'Work Program Template', 'file' => '5. work-program-template.docx', 'type' => 'DOCX'],
            ['id' => 6, 'title' => 'Emanating Template', 'file' => '6. emanating-template.xlsx', 'type' => 'XLSX'],
        ],
    ]);
})->name('templates.index');

Route::get('/templates/{template}', function (int $template): BinaryFileResponse {
    $templates = [
        1 => 'standard-template/app-template.xlsx',
        2 => 'standard-template/ppmp-template.xlsx',
        3 => '3. project-proposal-template.docx',
        4 => '4. project-brief-template.docx',
        5 => '5. work-program-template.docx',
        6 => '6. emanating-template.xlsx',
    ];

    abort_unless(array_key_exists($template, $templates), 404);

    $filePath = base_path('documents/' . $templates[$template]);
    abort_unless(is_file($filePath), 404);

    return response()->download($filePath, basename($templates[$template]));
})->whereNumber('template')->name('templates.download');
