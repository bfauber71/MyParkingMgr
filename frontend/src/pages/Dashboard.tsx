import { useState } from 'react'
import { Search, Plus, FileDown, FileUp, Car, Building, Users } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const sampleVehicles = [
  {
    id: '1',
    property: 'Sunset Apartments',
    tagNumber: 'PKG001',
    plateNumber: 'ABC123',
    state: 'CA',
    make: 'Toyota',
    model: 'Camry',
    color: 'Silver',
    year: '2020',
    aptNumber: '101',
    ownerName: 'John Doe',
    ownerPhone: '555-0100',
    ownerEmail: 'john@example.com',
    reservedSpace: 'A-15',
  },
  {
    id: '2',
    property: 'Harbor View Complex',
    tagNumber: 'PKG002',
    plateNumber: 'XYZ789',
    state: 'CA',
    make: 'Honda',
    model: 'Accord',
    color: 'Blue',
    year: '2019',
    aptNumber: '205',
    ownerName: 'Jane Smith',
    ownerPhone: '555-0200',
    ownerEmail: 'jane@example.com',
    reservedSpace: 'B-23',
  },
  {
    id: '3',
    property: 'Mountain Ridge',
    tagNumber: 'PKG003',
    plateNumber: 'DEF456',
    state: 'CA',
    make: 'Ford',
    model: 'F-150',
    color: 'Black',
    year: '2021',
    aptNumber: '301',
    ownerName: 'Bob Johnson',
    ownerPhone: '555-0300',
    ownerEmail: 'bob@example.com',
    reservedSpace: 'C-42',
  },
]

export default function Dashboard() {
  const [searchQuery, setSearchQuery] = useState('')
  const [vehicles] = useState(sampleVehicles)

  const filteredVehicles = vehicles.filter((vehicle) =>
    Object.values(vehicle).some((value) =>
      value?.toString().toLowerCase().includes(searchQuery.toLowerCase())
    )
  )

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border bg-card">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-primary">ManageMyParking</h1>
          <div className="flex items-center gap-4">
            <span className="text-sm text-muted-foreground">Demo User (Admin)</span>
            <Button variant="outline" size="sm">Logout</Button>
          </div>
        </div>
      </header>

      <main className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Total Vehicles</CardTitle>
              <Car className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{vehicles.length}</div>
              <p className="text-xs text-muted-foreground">Across all properties</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Properties</CardTitle>
              <Building className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">3</div>
              <p className="text-xs text-muted-foreground">Active properties</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">Users</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">1</div>
              <p className="text-xs text-muted-foreground">System users</p>
            </CardContent>
          </Card>
        </div>

        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Vehicle Search</CardTitle>
            <CardDescription>Search across all vehicle records</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4 mb-4">
              <div className="flex-1 relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search by tag, plate, owner, property..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Add Vehicle
              </Button>
              <Button variant="outline">
                <FileUp className="h-4 w-4 mr-2" />
                Import CSV
              </Button>
              <Button variant="outline">
                <FileDown className="h-4 w-4 mr-2" />
                Export CSV
              </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredVehicles.map((vehicle) => (
                <Card key={vehicle.id} className="hover:border-primary transition-colors">
                  <CardHeader className="pb-3">
                    <CardTitle className="text-lg">
                      {vehicle.year} {vehicle.color} {vehicle.make} {vehicle.model}
                    </CardTitle>
                    <CardDescription>{vehicle.property}</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-2 text-sm">
                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <span className="text-muted-foreground">Plate:</span>
                        <div className="font-mono font-semibold">{vehicle.plateNumber}</div>
                      </div>
                      <div>
                        <span className="text-muted-foreground">State:</span>
                        <div className="font-mono font-semibold">{vehicle.state}</div>
                      </div>
                      <div>
                        <span className="text-muted-foreground">Tag:</span>
                        <div className="font-mono font-semibold">{vehicle.tagNumber}</div>
                      </div>
                      <div>
                        <span className="text-muted-foreground">Apt:</span>
                        <div className="font-mono font-semibold">{vehicle.aptNumber}</div>
                      </div>
                    </div>
                    <div className="pt-2 border-t border-border">
                      <div className="text-muted-foreground">Owner:</div>
                      <div className="font-semibold">{vehicle.ownerName}</div>
                      <div className="text-xs text-muted-foreground">{vehicle.ownerPhone}</div>
                    </div>
                    <div>
                      <span className="text-muted-foreground text-xs">Reserved Space:</span>
                      <div className="font-mono font-semibold">{vehicle.reservedSpace}</div>
                    </div>
                    <div className="flex gap-2 pt-2">
                      <Button size="sm" variant="outline" className="flex-1">Edit</Button>
                      <Button size="sm" variant="destructive" className="flex-1">Delete</Button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {filteredVehicles.length === 0 && (
              <div className="text-center py-12 text-muted-foreground">
                <Car className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No vehicles found matching your search</p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="bg-blue-950/20 border-blue-800">
          <CardHeader>
            <CardTitle className="text-blue-400">Demo Mode</CardTitle>
            <CardDescription>
              This is a demonstration of the ManageMyParking frontend interface. 
              This application requires PHP 8.3+ and MySQL 8.0+ to run the full backend.
            </CardDescription>
          </CardHeader>
          <CardContent className="text-sm space-y-2">
            <p><strong>Key Features:</strong></p>
            <ul className="list-disc list-inside space-y-1 text-muted-foreground">
              <li>14-field vehicle tracking (tag, plate, owner info, etc.)</li>
              <li>Multi-property support with role-based access control</li>
              <li>Three user roles: Admin, User, Operator (read-only)</li>
              <li>Advanced search with full-text indexing</li>
              <li>CSV import/export for bulk operations</li>
              <li>Comprehensive audit logging</li>
              <li>Property management with up to 3 contacts per property</li>
            </ul>
          </CardContent>
        </Card>
      </main>
    </div>
  )
}
