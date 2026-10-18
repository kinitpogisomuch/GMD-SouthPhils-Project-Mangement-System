{{-- Row actions for one project: view, and (unless completed) assign employees / edit / archive-or-restore.
     Their modals and handlers come from partials.admin.project_actions. Param: $project --}}
<button class="action-btn view project-view-btn" type="button" title="View Project"
    data-id="{{ $project->id }}">
    <i data-lucide="eye"></i>
</button>
@if($project->status !== 'completed')
<button class="action-btn view assign-employee-btn" type="button" title="Assign Employees"
    data-id="{{ $project->id }}"
    data-name="{{ $project->name }}"
    data-assigned="{{ $project->assignedEmployees->pluck('id')->toJson() }}"
    data-est-days="{{ $project->estimated_working_days ?? 0 }}">
    <i data-lucide="users"></i>
</button>
<button class="action-btn view edit-project-btn" type="button" title="Edit Project"
    data-id="{{ $project->id }}"
    data-name="{{ $project->name }}"
    data-start-date="{{ $project->start_date->format('Y-m-d') }}"
    data-end-date="{{ $project->end_date->format('Y-m-d') }}"
    data-notes="{{ $project->notes }}"
    data-tank-items="{{ $project->tankItems->map(fn($t) => ['tank_type'=>$t->tank_type,'shape'=>$t->shape,'capacity'=>$t->capacity,'dimensions'=>$t->dimensions,'quantity'=>$t->quantity,'notes'=>$t->notes])->toJson() }}">
    <i data-lucide="pencil"></i>
</button>
<button class="action-btn view archive-project-btn" type="button"
    title="{{ $project->status === 'archived' ? 'Restore Project' : 'Archive Project' }}"
    data-id="{{ $project->id }}"
    data-name="{{ $project->name }}"
    data-archived="{{ $project->status === 'archived' ? '1' : '0' }}">
    <i data-lucide="{{ $project->status === 'archived' ? 'archive-restore' : 'archive' }}"></i>
</button>
@endif
