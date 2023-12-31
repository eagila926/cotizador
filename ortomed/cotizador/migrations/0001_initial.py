# Generated by Django 3.2.20 on 2023-08-23 18:40

from django.db import migrations, models
import django.db.models.deletion


class Migration(migrations.Migration):

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Clientes',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('nombre', models.TextField(verbose_name='Nombre')),
                ('cedula', models.TextField(null=True, unique=True, verbose_name='Cedula')),
                ('telefono', models.TextField(verbose_name='Telefono')),
                ('correo', models.TextField(verbose_name='correo')),
                ('direccion', models.TextField(verbose_name='Direccion')),
                ('tipo', models.TextField(verbose_name='Tipo')),
            ],
            options={
                'verbose_name': 'Cliente',
                'verbose_name_plural': 'Clientes',
            },
        ),
        migrations.CreateModel(
            name='Doctores',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('nombre', models.TextField(verbose_name='Nombre')),
                ('telefono', models.TextField(verbose_name='Telefono')),
                ('cedula', models.TextField(null=True, unique=True, verbose_name='cedula')),
                ('direccion', models.TextField(verbose_name='direccion')),
                ('ciudad', models.TextField(verbose_name='Ciudad')),
            ],
            options={
                'verbose_name': 'Doctor',
                'verbose_name_plural': 'Doctores',
            },
        ),
        migrations.CreateModel(
            name='Formulas',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('cod_formula', models.TextField(unique=True, verbose_name='Codigo Formula')),
                ('nombre', models.TextField(verbose_name='Nombre Formula')),
                ('comp_activos', models.TextField(verbose_name='Composicion')),
                ('cantidad', models.IntegerField(verbose_name='Cantidad')),
                ('unidad_compra', models.TextField(verbose_name='Unidad de compra')),
                ('dosis', models.IntegerField(verbose_name='Dosis')),
                ('posologia', models.IntegerField(verbose_name='Posologia')),
            ],
            options={
                'verbose_name': 'Formula',
                'verbose_name_plural': 'Formulas',
            },
        ),
        migrations.CreateModel(
            name='Inventario',
            fields=[
                ('cod_inven', models.IntegerField(primary_key=True, serialize=False, verbose_name='Codigo Inventario')),
                ('descripcion', models.TextField(verbose_name='Descripcion')),
                ('valor_costo', models.DecimalField(decimal_places=4, max_digits=10, verbose_name='Valor Costo')),
                ('valor_venta', models.DecimalField(decimal_places=4, max_digits=10, verbose_name='Valor Venta')),
                ('unidad_compra', models.TextField(max_length=3, verbose_name='Unidad Compra')),
                ('stock', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='Stock')),
                ('stock_min', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='Stock FIn')),
                ('m1', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='m1')),
                ('m2', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='m2')),
                ('m3', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='m3')),
                ('v1', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='v1')),
                ('v2', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='v2')),
                ('v3', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='v3')),
                ('vf1', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='vf1')),
                ('vf2', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='vf2')),
                ('vf3', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='vf3')),
                ('p1', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='p1')),
                ('p2', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='p2')),
                ('p3', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='p3')),
                ('factor', models.DecimalField(decimal_places=3, max_digits=15, verbose_name='Factor')),
                ('densidad', models.DecimalField(decimal_places=4, max_digits=15, verbose_name='Densidad')),
                ('tipo', models.TextField(verbose_name='Tipo')),
            ],
            options={
                'verbose_name': 'Inventario',
                'verbose_name_plural': 'Inventarios',
            },
        ),
        migrations.CreateModel(
            name='Usuario',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('cod_user', models.TextField(unique=True, verbose_name='Codigo')),
                ('nombre', models.TextField(verbose_name='Nombre')),
                ('apellido', models.TextField(verbose_name='Apellido')),
                ('correo', models.TextField(verbose_name='Correo')),
                ('tipo', models.TextField(verbose_name='Tipo')),
                ('ciudad', models.TextField(verbose_name='Ciudad')),
                ('pais', models.TextField(verbose_name='Pais')),
            ],
            options={
                'verbose_name': 'Usuario',
                'verbose_name_plural': 'Usuarios',
            },
        ),
        migrations.CreateModel(
            name='Visitas',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('nombre', models.TextField(verbose_name='Nombre')),
                ('cedula', models.TextField(null=True, unique=True, verbose_name='Cedula')),
                ('telefono', models.TextField(verbose_name='Telefono')),
                ('correo', models.TextField(verbose_name='Correo')),
                ('direccion', models.TextField(verbose_name='Direccion')),
                ('tipo', models.IntegerField(verbose_name='Tipo')),
                ('cod_user', models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.usuario', verbose_name='Codigo Usuario')),
            ],
            options={
                'verbose_name': 'Visita',
                'verbose_name_plural': 'Visitas',
            },
        ),
        migrations.CreateModel(
            name='Pedidos',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('descripcion', models.TextField(verbose_name='Descripcion')),
                ('fecha', models.DateTimeField(auto_now_add=True, verbose_name='Fecha')),
                ('estado', models.TextField(verbose_name='Estado')),
                ('cantidad', models.IntegerField(verbose_name='Cantidad')),
                ('observaciones', models.TextField(verbose_name='Observaciones')),
                ('cod_cliente', models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.clientes', verbose_name='Codigo Cliente')),
                ('cod_doc', models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.doctores', verbose_name='Codigo Doctor')),
                ('cod_user', models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.usuario', verbose_name='Codigo Usuario')),
            ],
            options={
                'verbose_name': 'Pedido',
                'verbose_name_plural': 'Pedidos',
            },
        ),
        migrations.AddField(
            model_name='doctores',
            name='cod_user',
            field=models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.usuario', verbose_name='Codigo Ususario'),
        ),
        migrations.AddField(
            model_name='clientes',
            name='cod_user',
            field=models.ForeignKey(on_delete=django.db.models.deletion.PROTECT, to='cotizador.usuario', verbose_name='Codigo Ususario'),
        ),
    ]
