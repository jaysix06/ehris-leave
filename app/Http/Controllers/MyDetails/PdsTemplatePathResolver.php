<?php

namespace App\Http\Controllers\MyDetails;

class PdsTemplatePathResolver
{
    public function resolve(): ?string
    {
        $projectTemplate = base_path('PDS.xlsx');

        $configuredTemplate = trim((string) config('pds.template_path', ''));

        $userProfile = trim((string) config('pds.user_profile', ''));
        $downloadCandidates = [];
        if ($userProfile !== '') {
            foreach ((array) config('pds.download_filenames', []) as $filename) {
                $downloadCandidates[] = $userProfile.'\\Downloads\\'.ltrim((string) $filename, '\\/');
            }
        }

        $candidates = [
            $projectTemplate,
            $configuredTemplate,
            storage_path('app/templates/ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet (1).xlsx'),
            storage_path('app/templates/ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet.xlsx'),
            storage_path('app/templates/deped-pds-template.xlsx'),
            storage_path('app/templates/DEPED-PDS.xlsx'),
            public_path('templates/deped-pds-template.xlsx'),
            public_path('deped-pds-template.xlsx'),
            ...$downloadCandidates,
        ];

        return $this->resolveExistingPath($candidates);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    public function resolveExistingPath(array $candidates): ?string
    {
        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
