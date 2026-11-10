<?php

namespace App\Services;

use App\Models\EditorialProfile;
use App\Models\MediaOutlet;
use Illuminate\Support\Facades\File;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class EditorialProfileWordReport
{
    /** @var array<string, string> */
    private const IDENTITY_FIELDS = [
        'audience' => 'Público destinatario',
        'focus' => 'Enfoque editorial',
        'tone' => 'Tono de comunicación',
        'style' => 'Estilo periodístico',
        'preferred_vocabulary' => 'Vocabulario preferido',
        'avoid_expressions' => 'Expresiones a evitar',
    ];

    /** @var array<string, string> */
    private const PRODUCTION_FIELDS = [
        'preferred_length' => 'Extensión preferida',
        'title_structure' => 'Estructura del título',
        'summary_style' => 'Estilo de bajada',
        'body_structure' => 'Organización del cuerpo',
        'quote_preferences' => 'Preferencias de citas',
        'output_format' => 'Formato de salida',
    ];

    /** @var array<string, string> */
    private const RESTRICTION_FIELDS = [
        'preferred_topics' => 'Temáticas preferidas',
        'excluded_topics' => 'Temáticas excluidas',
        'undesired_approaches' => 'Enfoques no deseados',
        'writing_rules' => 'Reglas específicas',
    ];

    public function create(MediaOutlet $mediaOutlet, EditorialProfile $profile): string
    {
        $directory = storage_path('app/private/reports');
        File::ensureDirectoryExists($directory);
        $path = tempnam($directory, 'editorial-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del informe editorial.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el documento Word.');
        }

        $archive->addFromString('[Content_Types].xml', $this->contentTypes());
        $archive->addFromString('_rels/.rels', $this->packageRelationships());
        $archive->addFromString('word/_rels/document.xml.rels', $this->documentRelationships());
        $archive->addFromString('word/styles.xml', $this->styles());
        $archive->addFromString('word/document.xml', $this->document($mediaOutlet, $profile));
        $archive->close();

        return $path;
    }

    private function document(MediaOutlet $mediaOutlet, EditorialProfile $profile): string
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8', 'yes');
        $writer->startElement('w:document');
        $writer->writeAttribute('xmlns:w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $writer->startElement('w:body');

        $this->paragraph($writer, 'Informe de línea editorial', 'Title');
        $this->paragraph($writer, $mediaOutlet->name, 'Subtitle');
        $this->paragraph($writer, 'Este documento reúne la configuración editorial vigente utilizada por NEURIA para adaptar la investigación y la redacción de contenidos del medio.');
        $this->field($writer, 'Perfil', $profile->name);
        $this->field($writer, 'Idioma', $profile->language);
        $this->field($writer, 'Versión vigente', (string) $profile->current_version);
        $this->field($writer, 'Fecha del informe', now('America/Argentina/Buenos_Aires')->format('d/m/Y H:i'));

        $this->paragraph($writer, 'Identidad editorial', 'Heading1');
        $this->fields($writer, self::IDENTITY_FIELDS, $profile->identity ?? []);

        $this->paragraph($writer, 'Producción de contenidos', 'Heading1');
        $this->fields($writer, self::PRODUCTION_FIELDS, $profile->production ?? []);
        $this->field($writer, 'Usar subtítulos', ($profile->production['use_subtitles'] ?? false) ? 'Sí' : 'No');

        $this->paragraph($writer, 'Fuentes y restricciones', 'Heading1');
        $this->field($writer, 'Fuentes preferidas', $profile->sources_config['preferred'] ?? null);
        $this->field($writer, 'Fuentes restringidas', $profile->sources_config['restricted'] ?? null);
        $this->fields($writer, self::RESTRICTION_FIELDS, $profile->restrictions ?? []);

        $this->paragraph($writer, 'Fuentes registradas', 'Heading2');
        if ($profile->sources->isEmpty()) {
            $this->paragraph($writer, 'No hay fuentes adicionales registradas.');
        } else {
            foreach ($profile->sources as $source) {
                $description = collect([$source->name, $source->url, $source->type, $source->notes])
                    ->filter(fn (?string $value): bool => filled($value))
                    ->implode(' | ');
                $this->paragraph($writer, '• '.$description);
            }
        }

        $this->paragraph($writer, 'Instrucciones adicionales autorizadas', 'Heading1');
        $this->paragraph($writer, $this->displayValue($profile->additional_instructions));

        $this->paragraph($writer, 'Prompt editorial consolidado', 'Heading1');
        $this->paragraph($writer, 'Aplicar las siguientes preferencias editoriales al contenido destinado a '.$mediaOutlet->name.'.');
        foreach ($this->promptLines($profile) as $line) {
            $this->paragraph($writer, $line);
        }

        $writer->startElement('w:sectPr');
        $writer->startElement('w:pgSz');
        $writer->writeAttribute('w:w', '11906');
        $writer->writeAttribute('w:h', '16838');
        $writer->endElement();
        $writer->startElement('w:pgMar');
        $writer->writeAttribute('w:top', '1134');
        $writer->writeAttribute('w:right', '1134');
        $writer->writeAttribute('w:bottom', '1134');
        $writer->writeAttribute('w:left', '1134');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $values
     */
    private function fields(XMLWriter $writer, array $labels, array $values): void
    {
        foreach ($labels as $key => $label) {
            $this->field($writer, $label, $values[$key] ?? null);
        }
    }

    private function field(XMLWriter $writer, string $label, mixed $value): void
    {
        $this->paragraph($writer, $label, 'Heading2');
        $this->paragraph($writer, $this->displayValue($value));
    }

    private function paragraph(XMLWriter $writer, string $text, string $style = 'Normal'): void
    {
        $lines = preg_split('/\R/u', $text) ?: [''];
        foreach ($lines as $line) {
            $writer->startElement('w:p');
            $writer->startElement('w:pPr');
            $writer->startElement('w:pStyle');
            $writer->writeAttribute('w:val', $style);
            $writer->endElement();
            $writer->endElement();
            $writer->startElement('w:r');
            $writer->startElement('w:t');
            $writer->writeAttribute('xml:space', 'preserve');
            $writer->text($line);
            $writer->endElement();
            $writer->endElement();
            $writer->endElement();
        }
    }

    private function displayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            $value = implode(', ', array_filter($value, fn (mixed $item): bool => is_scalar($item)));
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : 'Sin configurar.';
    }

    /** @return list<string> */
    private function promptLines(EditorialProfile $profile): array
    {
        $lines = [];
        foreach ([
            [self::IDENTITY_FIELDS, $profile->identity ?? []],
            [self::PRODUCTION_FIELDS, $profile->production ?? []],
            [self::RESTRICTION_FIELDS, $profile->restrictions ?? []],
        ] as [$labels, $values]) {
            foreach ($labels as $key => $label) {
                if (filled($values[$key] ?? null)) {
                    $lines[] = $label.': '.$this->displayValue($values[$key]);
                }
            }
        }
        foreach (['Fuentes preferidas' => 'preferred', 'Fuentes restringidas' => 'restricted'] as $label => $key) {
            if (filled($profile->sources_config[$key] ?? null)) {
                $lines[] = $label.': '.$this->displayValue($profile->sources_config[$key]);
            }
        }
        if (filled($profile->additional_instructions)) {
            $lines[] = 'Instrucciones adicionales: '.$profile->additional_instructions;
        }

        return $lines ?: ['No se registraron indicaciones editoriales adicionales.'];
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>';
    }

    private function packageRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
    }

    private function documentRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:sz w:val="22"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="160"/></w:pPr><w:rPr><w:b/><w:color w:val="000000"/><w:sz w:val="40"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="320"/></w:pPr><w:rPr><w:color w:val="555555"/><w:sz w:val="26"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="300" w:after="140"/></w:pPr><w:rPr><w:b/><w:color w:val="000000"/><w:sz w:val="30"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="180" w:after="60"/></w:pPr><w:rPr><w:b/><w:color w:val="000000"/><w:sz w:val="23"/></w:rPr></w:style></w:styles>';
    }
}
