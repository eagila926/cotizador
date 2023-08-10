from django.http import HttpResponse
from django.shortcuts import render

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
    return render(request, 'formula.html')

def inventario(request):
    return render(request, 'inventario.html')

def pedidos(request):
    return render(request, 'pedidos.html')

def reportes(request):
    return render(request, 'reportes.html')
