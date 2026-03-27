<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Imports\ProjectBriefImport;
use App\Imports\WorkProgramImport;
use App\Models\ProjectBrief;
use App\Models\ProjectProposal;
use App\Models\WorkProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFundDocumentImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $documentType,
        public readonly int $documentId,
    ) {
        $this->onQueue('default');
    }

    public function handle(WorkProgramImport $workProgramImport, ProjectBriefImport $projectBriefImport): void
    {
        if ($this->documentType === 'work_program') {
            $workProgram = WorkProgram::query()->find($this->documentId);

            if (! $workProgram) {
                return;
            }

            $workProgramImport->import($workProgram);

            return;
        }

        if ($this->documentType === 'project_brief') {
            $projectBrief = ProjectBrief::query()->find($this->documentId);

            if (! $projectBrief) {
                return;
            }

            $projectBriefImport->import($projectBrief);

            return;
        }

        if ($this->documentType === 'project_proposal') {
            ProjectProposal::query()->find($this->documentId);
        }
    }
}
