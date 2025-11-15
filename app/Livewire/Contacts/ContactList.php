<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class ContactsList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedContactId = null;

    protected $listeners = [
        'contactUpdated' => '$refresh',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectContact($id)
    {
        $this->selectedContactId = $id;
        $this->dispatch('contactSelected', id: $id);
    }

    public function render()
    {
        $contacts = Contact::where('tenant_id', auth()->user()->tenant_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('full_name', 'ILIKE', "%{$this->search}%")
                      ->orWhere('email', 'ILIKE', "%{$this->search}%")
                      ->orWhere('phone', 'ILIKE', "%{$this->search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(50);

        return view('livewire.contacts.contacts-list', [
            'contacts' => $contacts
        ]);
    }
}
