from typing import Any, Dict
from django.views.generic import ListView, CreateView, UpdateView
from django.http import HttpResponse
from django.shortcuts import render

from .models import Inventario
from .forms import InventarioForm


# Create your views here.
def login(request):
    return render( request, 'login.html')

class InventarioListView (ListView):
    model = Inventario
    template_name = 'inventario.html'

    def get_context_data(self, **kwargs):
        context =super().get_context_data(**kwargs)
        context['title'] = 'Inventario'
        
        return context
    
class InventarioCreateview(CreateView):
    model = Inventario
    template_name = 'mod_inventario.html'

    def get_context_data(self, **kwargs):
        context =super().get_context_data(**kwargs)
        context['title'] = 'Agregar Inventario'
        
        return context

