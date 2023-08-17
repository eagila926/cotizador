from django.http import HttpResponse
from django.shortcuts import render

from cotizador.models import Inventario
from django.core.paginator import Paginator

# Create your views here.
def login(request):
    return render( request, 'login.html')

def comercial(request):
    return render( request, 'comercial.html')

def dashboard(request):
    return render( request, 'dashboard.html')

def etiquetas(request):
    return render(request, 'etiquetas.html')

def facturas(request):
    return render(request, 'facturas.html')

def formula(request):

    data = {
        'inventarios': Inventario.objects.all(),
        # 'paginator': Paginator('inventarios',10),
        # 'page_number': request.GET.get('page'),
        # 'page_obj': 'paginator'.get('page_number')
    }
    return render(request, 'formula.html', data)

def inventario(request):
    return render(request, 'inventario.html')

def pedidos(request):
    return render(request, 'pedidos.html')

def reportes(request):
    return render(request, 'reportes.html')

def home(request):
    return render(request, 'home.html')
