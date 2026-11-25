@extends('helpdesk.layout', ['showSidebar' => false])
@section('title','Open a ticket')
@section('main-class','ticket-create-page')
@section('subtitle','Choose the department that handles your concern, then select a category and help topic.')
@section('content')<section class="card max-form"><form method="post" enctype="multipart/form-data" action="{{ auth()->check() ? route('helpdesk.store') : route('support.store') }}">@csrf
@unless(auth()->user()?->isUser())<div class="field"><label for="requester_name">Requester name</label><input id="requester_name" name="requester_name" required value="{{ old('requester_name') }}"></div><div class="field"><label for="requester_email">Requester email</label><input type="email" id="requester_email" name="requester_email" required value="{{ old('requester_email') }}"></div>@endunless
<div class="field">
    <label for="department_id">Department</label>
    <select id="department_id" name="department_id" required>
        <option value="" disabled @selected(! $departments->contains('id', old('department_id')))>Select a department</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" @selected((int) old('department_id') === $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
</div>
<div class="field"><label for="category">Category</label><select id="category" name="category_id" required><option value="">Select a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" data-departments="{{ $category->departments->modelKeys() ? implode(',', $category->departments->modelKeys()) : '' }}" @selected((int)old('category_id')===$category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div class="field"><label for="help_topic">Help topic</label><select id="help_topic" name="help_topic_id"><option value="">Select a help topic</option>@foreach($topics as $topic)<option data-department="{{ $topic->settings['department_id'] ?? '' }}" data-category="{{ $topic->settings['category_id'] ?? '' }}" value="{{ $topic->id }}" @selected((int)old('help_topic_id')===$topic->id)>{{ $topic->name }}</option>@endforeach</select></div>
<div class="field"><label for="priority">Priority</label><select id="priority" name="priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority',$dashboardSettings['default_priority'])===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
<div class="field"><label for="title">Subject</label><input id="title" name="title" required maxlength="255" value="{{ old('title') }}"></div>
<div class="field"><label for="description">Describe the issue</label><textarea id="description" name="description" required maxlength="20000" rows="7">{{ old('description') }}</textarea></div>
<div class="field">
    <label for="attachment">Attachment (optional, up to 10 MB)</label>
    <input id="attachment" type="file" name="file" accept=".pdf,.png,.jpg,.jpeg,.gif,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip">
</div>
@foreach($fields as $field)@php($s=$field->settings)<div class="field custom-field" data-topic="{{ $s['topic_id'] ?? '' }}" data-form="{{ $s['form_id'] ?? '' }}"><label for="custom-{{ $field->id }}">{{ $field->name }}{{ !empty($s['required']) ? ' *' : '' }}</label>
@if($s['type']==='textarea')<textarea id="custom-{{ $field->id }}" name="custom_fields[{{ $s['key'] }}]" data-required="{{ !empty($s['required']) ? 1 : 0 }}">{{ old('custom_fields.'.$s['key']) }}</textarea>
@elseif($s['type']==='select')<select id="custom-{{ $field->id }}" name="custom_fields[{{ $s['key'] }}]" data-required="{{ !empty($s['required']) ? 1 : 0 }}"><option value="">Select…</option>@foreach($field->selectOptions() as $option)<option @selected(old('custom_fields.'.$s['key'])===$option)>{{ $option }}</option>@endforeach</select>
@else<input id="custom-{{ $field->id }}" type="{{ $s['type'] }}" @if($s['type']==='number') step="any" @endif name="custom_fields[{{ $s['key'] }}]" value="{{ old('custom_fields.'.$s['key']) }}" data-required="{{ !empty($s['required']) ? 1 : 0 }}">@endif
<small class="muted">{{ $field->description }}</small></div>@endforeach
<button>Submit ticket</button><p class="footer-note">Routing rules may set priority and category based on the selected help topic.</p></form></section>@endsection
@push('scripts')
<script>
const topic=document.getElementById('help_topic');
const formsByTopic={{ Illuminate\Support\Js::from($topics->mapWithKeys(fn($topic) => [$topic->id => array_map('strval', $topic->settings['form_ids'] ?? [])])) }};
function updateFields(){document.querySelectorAll('.custom-field').forEach(f=>{
    const topicMatches=!f.dataset.topic||f.dataset.topic===topic.value;
    const formMatches=!f.dataset.form||(formsByTopic[topic.value]||[]).includes(f.dataset.form);
    const visible=topicMatches&&formMatches;
    f.classList.toggle('hidden',!visible);
    f.querySelectorAll('input,select,textarea').forEach(i=>{i.disabled=!visible;i.required=visible&&i.dataset.required==='1';});
});}
const category=document.getElementById('category');
const department=document.getElementById('department_id');
const topicOptions=Array.from(topic.options).filter(option=>option.value);
const topicPlaceholder=topic.options[0];

function updateCategories(){
    for(const option of category.options){
        const visible=!option.value||(department.value&&option.dataset.departments.split(',').includes(department.value));
        option.hidden=!visible;
        option.disabled=!visible;
    }
    if(category.selectedOptions[0]?.disabled) category.value='';
    category.disabled=!department.value;
    category.options[0].textContent=department.value&&![...category.options].some(option=>option.value&&!option.disabled)
        ? 'No categories configured for this department'
        : 'Select a category';
    updateTopics();
}

function matchesDepartment(option){
    return !department.value||option.dataset.department===department.value;
}
function updateTopics(){
    const selectedTopic = topic.value;
    const matchingTopics = category.value
        ? topicOptions.filter(option => matchesDepartment(option) && option.dataset.category === category.value)
        : [];
    topicPlaceholder.textContent = 'Select a category first';
    if (category.value) {
        topicPlaceholder.textContent = matchingTopics.length ? 'Select a help topic' : 'No help topics available';
    }
    topic.replaceChildren(topicPlaceholder, ...matchingTopics);
    topic.value = matchingTopics.some(option => option.value === selectedTopic) ? selectedTopic : '';
    topic.disabled = matchingTopics.length === 0;
    updateFields();
}
category.addEventListener('change',updateTopics);
department.addEventListener('change',updateCategories);
topic.addEventListener('change',updateFields);
updateCategories();
</script>
@endpush


