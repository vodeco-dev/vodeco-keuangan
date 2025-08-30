<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with('client')->orderBy('name')->paginate(10);
        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        return view('projects.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
        ]);
        Project::create($request->only('name', 'client_id'));
        return redirect()->route('projects.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function edit(Project $project): View
    {
        $clients = Client::orderBy('name')->get();
        return view('projects.edit', compact('project', 'clients'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
        ]);
        $project->update($request->only('name', 'client_id'));
        return redirect()->route('projects.index')->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->transactions()->exists()) {
            return redirect()->route('projects.index')->with('error', 'Proyek tidak dapat dihapus karena memiliki transaksi.');
        }
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Proyek berhasil dihapus.');
    }
}
