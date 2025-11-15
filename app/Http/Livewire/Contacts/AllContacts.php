<?php

namespace App\Http\Livewire\Contacts;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AllContacts extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedContactId = null;

    protected $paginationTheme = 'bootstrap'; // or 'tailwind' depending on your stack

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function selectContact(int $contactId): void
    {
        // Ensure the contact belongs to the current tenant (safety)
        $contact = Contact::forCurrentTenant()->findOrFail($contactId);
        $this->selectedContactId = $contact->id;
    }

    public function getSelectedContactProperty()
    {
        if (!$this->selectedContactId) {
            return null;
        }

        return Contact::forCurrentTenant()->find($this->selectedContactId);
    }

    public function mount(): void
    {
        // Pre-select first contact for current tenant, if any
        $first = Contact::forCurrentTenant()
            ->orderBy('full_name')
            ->first();

        $this->selectedContactId = $first?->id;
    }

    public function render(): View
    {
        $query = Contact::forCurrentTenant();

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        $contacts = $query
            ->orderBy('full_name')
            ->paginate(50);

        // Ensure selected contact is on current page, or default to first
        if ($contacts->isNotEmpty()) {
            if (!$this->selectedContactId ||
                !$contacts->contains('id', $this->selectedContactId)) {
                $this->selectedContactId = $contacts->first()->id;
            }
        } else {
            $this->selectedContactId = null;
        }

        return view('livewire.contacts.all-contacts', [
            'contacts' => $contacts,
        ]);
    }
}
