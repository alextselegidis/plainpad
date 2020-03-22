import React from 'react';

const Notes = React.lazy(() => import('./views/Notes'));
const Settings = React.lazy(() => import('./views/Settings'));
const Profile = React.lazy(() => import('./views/Profile'));
const About = React.lazy(() => import('./views/About'));
const Help = React.lazy(() => import('./views/Help'));
const Users = React.lazy(() => import('./views/Users/Users'));

// https://github.com/ReactTraining/react-router/tree/master/packages/react-router-config
const routes = [
  { path: '/settings', name: 'Settings', component: Settings },
  { path: '/profile', name: 'Profile', component: Profile },
  { path: '/about', name: 'About', component: About },
  { path: '/help', name: 'Help', component: Help },
  { path: '/notes', exact: true, name: 'Notes', component: Notes },
  { path: '/notes/:id', exact: true, name: 'Notes', component: Notes },
  { path: '/users', exact: true,  name: 'Users', component: Users },
];

export default routes;
